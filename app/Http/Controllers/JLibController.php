<?php

namespace App\Http\Controllers;

use EasyWeChat\Kernel\Messages\Message;
use function foo\func;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class JLibController extends Controller
{
    const SENSITIVE_WORDS = [
        'search'  => ['素人娘', '盗撮', '肉奴隷', '発射', '大乱交', '2穴中出'],
        'replace' => ['素x人x娘', '盗x撮', '肉x奴x隷', '発x射', '大x乱x交', '2x穴x中x出']
    ];

    const QUALITY_HD = 1;
    const QUALITY_SD = 2;

    public function __construct()
    {
        $this->opener = new Client([
            'base_uri'    => config('jlib.javbus_base_url'),
            'http_errors' => false,
            'verify'      => false
        ]);
    }

    public function index()
    {
        $app = app('wechat.official_account');
        $app->server->push(function ($message) {
            $content = $message['Content'];
            if (substr($content, 0, 2) == '@@') {
                // 只查询磁链(标清)
                $content = substr($content, 2);

                return $this->get_magnet($content, self::QUALITY_SD) ?: '请注意大写和连字符, 例如 ABS-130';
            } elseif (substr($content, 0, 1) == '@') {
                // 只查询磁链(高清, 如果有)
                $content = substr($content, 1);

                return $this->get_magnet($content) ?: '请注意大写和连字符, 例如 ABS-130';
            } elseif ($content == '#') {
                // 获取随机番号
                return $this->rand_code_from_cache();
            } else {
                // 查询全部信息
                return $this->origin_query($content);
            }
        }, Message::TEXT);

        $app->server->push(function ($message) {
            return $this->get_info_by_image($message['PicUrl']);
        }, Message::IMAGE);

        return $app->server->serve();
    }

    public function get_info_by_image($image_url)
    {

    }

    /**
     * 传入正规拼写的番号, 返回查询到的磁链, 查不到则返回false
     *
     * @param string $code
     * @param int    $hd
     * @param array  $extra_data
     * @return mixed
     */
    public function get_magnet($code, $hd = self::QUALITY_HD, $extra_data = null)
    {
        if ($extra_data) {
            $cover_match = $extra_data['cover_match'];
            $gid_match   = $extra_data['gid_match'];
            $uc_match    = $extra_data['uc_match'];
        } else {
            $cover_pattern = '/<a class="bigImage" href="(.+?)">/';
            $gid_pattern   = '/gid *= *(\d+)/';
            $uc_pattern    = '/uc *= *(\d+);/';
            // 以上三个正则用于匹配 ajax 查询 javbus 上的磁链所需要的参数

            $res = $this->opener->get('/' . $code)->getBody()->getContents();

            preg_match($gid_pattern, $res, $gid_match);
            preg_match($uc_pattern, $res, $uc_match);
            preg_match($cover_pattern, $res, $cover_match);
        }

        // 伪造ajax查询所必要的headers
        $res = $this->opener->get(
            '/ajax/uncledatoolsbyajax.php?gid=' . $gid_match[1] . '&img=' .
            $cover_match[1] . '&uc=' . $uc_match[1], [
                'headers' => [
                    'Referer' => config('jlib.javbus_base_url')
                ]
            ]
        )->getBody()->getContents();

        $hd_mag_pattern     = '#<td width="70%".+?>\s*<a.+?href="(magnet:\?xt=urn:btih:\w{40}).*?">\s*.+?<a#';  // 用于匹配javbus高清搜索结果的正则
        $normal_mag_pattern = '#<td width="70%".+?>\s*<a.+?href="(magnet:\?xt=urn:btih:\w{40}).*?">\s*\S+\s*</a#';  // 用于匹配javbus标清搜索结果的正则

        preg_match_all($hd_mag_pattern, $res, $hd_mag_match);
        preg_match_all($normal_mag_pattern, $res, $normal_mag_match);

        if ($hd == self::QUALITY_HD) {
            // 需要高清
            if ($hd_mag_match[1]) {
                return $hd_mag_match[1][0];
            }
            if ($normal_mag_match[1]) {
                return $normal_mag_match[1][0];
            }

            return false;
        }
        if ($hd == self::QUALITY_SD) {
            // 不需要高清
            if ($normal_mag_match[1]) {
                return $normal_mag_match[1][0];
            }
            if ($hd_mag_match[1]) {
                return $hd_mag_match[1][0];
            }

            return false;
        }

        return false;
    }

    /**
     * 传入搜索结果唯一的番号, 需要正规拼写, 返回查询到的信息(带磁链)
     *
     * @param string $code
     * @return string | boolean
     */
    public function get_info($code)
    {
        $title_pattern = '/<h3>(.+)<\/h3>/';
        $date_pattern  = '/<\/span> *(\d+-\d+-\d+) *<\/p>/';
        $cover_pattern = '/<a class="bigImage" href="(.+?)">/';
        $gid_pattern   = '/gid *= *(\d+)/';
        $uc_pattern    = '/uc *= *(\d+);/';

        $res = $this->opener->get('/' . $code)->getBody()->getContents();

        preg_match($title_pattern, $res, $title_match);
        preg_match($date_pattern, $res, $date_match);
        preg_match($cover_pattern, $res, $cover_match);
        preg_match($gid_pattern, $res, $gid_match);
        preg_match($uc_pattern, $res, $uc_match);

        if (!isset($title_match[1])) {
            return false;
        }

        if ('0000-00-00' == $date_match[1]) {
            $date_match[1] = '未知';
        }
        $response = '车牌&车型&司机: ' . $title_match[1]
                    . "\n" . '发车日期: ' . $date_match[1];


        $response .= "\n" . '<a href="'
                     . str_replace('javbus.com', 'javcdn.pw', $cover_match[1])
                     . '">封面图</a>';

        $magnet   = $this->get_magnet(
            $code, self::QUALITY_HD,
            compact('gid_match', 'uc_match', 'cover_match')
        ) ?: '找不到神秘代码';
        $response .= "\n" . $magnet;

        return str_replace(self::SENSITIVE_WORDS['search'], self::SENSITIVE_WORDS['replace'], $response);
    }

    /**
     * 传入用户的原始输入, 返回查询到的信息(带磁链), 查不到则返回jav_lib高评价里的随机番号
     *
     * @param string $code
     * @return string
     */
    public function origin_query($code)
    {
        $movie_pattern = '/class="movie-box" href="(.+)">/';  // 单页影片数
        $pages_pattern = '#href="/search/\S+/(\d+)">\d+#';  // 页数

        $promises = [
            $this->opener->getAsync('/search/' . urlencode($code)),
            $this->opener->getAsync('/uncensored/search/' . urlencode($code))
        ];

        $results        = Promise\unwrap($promises);
        $res            = $results[0]->getBody()->getContents();
        $uncensored_res = $results[1]->getBody()->getContents();

        preg_match_all($movie_pattern, $res, $movie_match);
        preg_match_all($movie_pattern, $uncensored_res, $unmovie_match);

        if (count($movie_match[1]) == 1) {
            // 用户搜索结果唯一
            $code = explode('/', $movie_match[1][0]);

            return $this->get_info(end($code));
        }

        if (count($unmovie_match[1]) == 1) {
            // 用户搜索结果唯一
            $code = explode('/', $unmovie_match[1][0]);

            return $this->get_info(end($code));
        }

        // 用户搜索结果不唯一, 可能需要翻页处理
        preg_match_all($pages_pattern, $res, $pages_match);
        preg_match_all($pages_pattern, $uncensored_res, $unpages_match);  // 无码页数

        if (count($pages_match[1]) || count($unpages_match[1])) {
            // 需要翻页
            return '结果太多, 尝试缩小搜索范围吧';
        }

        if (
            in_array(config('jlib.javbus_base_url') . $code, $movie_match[1])
            || in_array(config('jlib.javbus_base_url') . $code, $unmovie_match[1])
        ) {
            // 搜 SW-220, 结果有 DKSW-220 和 SW-220 的情况, 需要返回 SW-220 的信息
            return $this->get_info($code);
        }

        if ((count($movie_match[1]) > 1) || (count($unmovie_match[1]) > 1)) {
            // 正常的模糊查询
            $response = "你是不是要找:";
            foreach ($movie_match[1] as $v) {
                $arr      = explode('/', $v);
                $response = $response . "\n" . end($arr);
            }
            foreach ($unmovie_match[1] as $v) {
                $arr      = explode('/', $v);
                $response = $response . "\n" . end($arr);
            }

            return $response;
        }

        return '没有此车牌, 获取随机车牌请回复 #';
    }

    /**
     * 从缓存随机取一条信息
     *
     * @return string
     */
    public function rand_code_from_cache()
    {
        $a = (array)cache()->get('best_rated');

        return $a[mt_rand(0, count($a) - 1)];
    }
}
