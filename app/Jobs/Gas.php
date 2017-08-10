<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class Gas implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    protected function opener()
    {
        return new \GuzzleHttp\Client([
            'verify' => false,
            'proxy'  => env('GUZZLE_HTTP_PROXY', null)
        ]);
    }

    public function handle()
    {
        $promises = [
            'javbus' => $this->javbus($this->config['gid'], $this->config['uc']),
//            'avgle' => $this->avgle($this->config['code']),
        ];

        $results = \GuzzleHttp\Promise\unwrap($promises);

        foreach ($results as $index => $result) {
            $method = 'parse_' . $index;
            dump($this->$method($result));
        }
    }

    protected function javbus($gid, $uc)
    {
        return $this->opener()->getAsync(
            "https://www.javbus.com/ajax/uncledatoolsbyajax.php?gid={$gid}&uc={$uc}", [
                'headers' => [
                    'referer' => 'https://www.javbus.com'
                ]
            ]
        );
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     */
    protected function parse_javbus($response)
    {
        $res = $response->getBody()->getContents();
        preg_match_all(
            '#' .
            'href="(?P<magnet>magnet:\?xt=urn:btih:\w{40})\S*">' .
            '\s*(?P<name>\S+)' .
            '\s*(?P<hd><a .+?>HD</a>)?' .
            '.+?href="(?P=magnet)\S*">\s*(?P<size>\S+)\s*</a>' .
            '.+?href="(?P=magnet)\S*">\s*(?P<date>\S+)\s*</a>' .
            '#s',
            $res,
            $matches
        );


        return array_map(function ($magnet, $name, $hd, $size, $date) {
            $return       = compact('magnet', 'name', 'size', 'date');
            $return['hd'] = empty($hd) ? false : true;
            return $return;
        }, $matches['magnet'], $matches['name'], $matches['hd'], $matches['size'], $matches['date']);
    }

    protected function avgle($code)
    {
        return $this->opener()->getAsync("https://api.avgle.com/v1/jav/{$code}/0");
    }

    protected function parse_avgle($response)
    {

    }
}
