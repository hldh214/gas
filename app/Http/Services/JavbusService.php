<?php


namespace App\Http\Services;


use App\Console\Commands\RandCrawler;
use Exception;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class JavbusService
{
    const QUALITY_HD = 1;
    const QUALITY_SD = 2;
    /**
     * @var array
     */
    private $guzzle_options;

    /**
     * JavbusService constructor.
     */
    public function __construct()
    {
        $this->guzzle_options = [
            'http_errors' => false,
            'timeout'     => config('jlib.timeout'),
            'verify'      => false
        ];
    }

    /**
     * 传入搜索结果唯一的番号, 需要正规拼写, 返回查询到的信息(带磁链)
     *
     * @param  string  $code
     * @return array | boolean
     * @throws Exception
     */
    public function get_info($code)
    {
        $title_pattern           = '/<h3>(.+)<\/h3>/';
        $date_pattern            = '/<\/span> *(\d+-\d+-\d+) *<\/p>/';
        $cover_pattern           = /** @lang RegExp */
            '/<a class="bigImage" href="(.+?)">/';
        $gid_pattern             = '/gid *= *(\d+)/';
        $uc_pattern              = '/uc *= *(\d+);/';
        $uncensored_flag_pattern = '/<li\s*class="active"><a\s*href=".+uncensored">/';
        $pic_pattern             = /** @lang RegExp */
            '/<a class="sample-box" href="(.+?)">/';

        try {
            $res = Http::withOptions($this->guzzle_options)->get(config('jlib.javbus_base_url') . '/' . $code)->body();
        } catch (Exception $exception) {
            return false;
        }

        preg_match($title_pattern, $res, $title_match);
        preg_match($date_pattern, $res, $date_match);
        preg_match($cover_pattern, $res, $cover_match);
        preg_match($gid_pattern, $res, $gid_match);
        preg_match($uc_pattern, $res, $uc_match);
        preg_match($uncensored_flag_pattern, $res, $uncensored_flag_match);
        preg_match_all($pic_pattern, $res, $pic_match);

        if (!isset($title_match[1])) {
            return false;
        }

        if ('0000-00-00' == $date_match[1]) {
            $date_match[1] = '未知';
        }

        $title   = $title_match[1];
        $date    = $date_match[1];
        $type    = empty($uncensored_flag_match) ? '骑兵' : '步兵';
        $cover   = str_replace('javbus.com', 'javcdn.pw', $cover_match[1]);
        $preview = $pic_match[1];
        $magnet  = $this->get_magnet(
            $code, self::QUALITY_HD,
            compact('gid_match', 'uc_match', 'cover_match')
        ) ?: '';

        return compact('title', 'date', 'type', 'cover', 'preview', 'magnet');
    }

    /**
     * 传入正规拼写的番号, 返回查询到的磁链, 查不到则返回false
     *
     * @param  string  $code
     * @param  int  $hd
     * @param  array  $extra_data
     * @return mixed
     */
    public function get_magnet($code, $hd = self::QUALITY_HD, $extra_data = null)
    {
        if ($extra_data) {
            $cover_match = $extra_data['cover_match'];
            $gid_match   = $extra_data['gid_match'];
            $uc_match    = $extra_data['uc_match'];
        } else {
            $cover_pattern = /** @lang RegExp */
                '/<a class="bigImage" href="(.+?)">/';
            $gid_pattern   = '/gid *= *(\d+)/';
            $uc_pattern    = '/uc *= *(\d+);/';
            // 以上三个正则用于匹配 ajax 查询 javbus 上的磁链所需要的参数

            try {
                $res = Http::withOptions($this->guzzle_options)->get(
                    config('jlib.javbus_base_url') . '/' . $code
                )->body();
            } catch (ConnectException $exception) {
                return false;
            }

            if (!preg_match($gid_pattern, $res, $gid_match)) {
                return false;
            }
            preg_match($uc_pattern, $res, $uc_match);
            preg_match($cover_pattern, $res, $cover_match);
        }

        try {
            $res = Http::withOptions(array_merge([
                'headers' => [
                    'Referer' => config('jlib.javbus_base_url')
                ]
            ], $this->guzzle_options))->get(
                config('jlib.javbus_base_url') . '/ajax/uncledatoolsbyajax.php?gid=' .
                $gid_match[1] . '&img=' . $cover_match[1] . '&uc=' . $uc_match[1]
            )->body();
        } catch (ConnectException $exception) {
            return false;
        }

        $hd_mag_pattern     = /** @lang RegExp */
            '#<td width="70%".+?>\s*<a.+?href="(magnet:\?xt=urn:btih:\w{40}).*?">\s*.+?<a#';
        $normal_mag_pattern = /** @lang RegExp */
            '#<td width="70%".+?>\s*<a.+?href="(magnet:\?xt=urn:btih:\w{40}).*?">\s*\S+\s*</a#';

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
     * @return bool|string
     * @throws Exception
     */
    public function rand()
    {
        /** @var PhpRedisConnection $redis */
        $redis = Redis::connection();

        $res = $redis->lpop(RandCrawler::RAND_LIST);

        if (!$res) {
            return false;
        }

        return json_decode($res, true);
    }
}
