<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class Crawl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'retrieve best_rated from javlibrary';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $codes    = [];
        $promises = [];
        $info     = [];

        $jlib         = app()->make('App\Http\Controllers\JLibController');
        $code_pattern = '/<a href="\.\/\?v=.+?" title="(\S+) {1}/';
        $client       = new Client([
            'base_uri' => config('jlib.javlibrary_base_url'),
            'verify'   => false
        ]);

        for ($page = 1; $page < 10; $page++) {
            $promises[] = $client->getAsync('/tw/vl_bestrated.php?list&mode=&page=' . $page);
        }

        $results = Promise\unwrap($promises);

        foreach ($results as $each) {
            preg_match_all($code_pattern, $each->getBody()->getContents(), $code_match);
            $codes = array_merge($codes, $code_match[1]);
        }

        foreach ($codes as $code) {
            $res = $jlib->get_info($code);
            if ($res) {
                $info[] = $res;
            }
        }

        cache()->forever('best_rated', $info);
    }
}
