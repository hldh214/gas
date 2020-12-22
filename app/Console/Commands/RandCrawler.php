<?php

namespace App\Console\Commands;

use App\Http\Services\JavbusService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class RandCrawler extends Command
{
    const RAND_LIST    = 'rand_list';
    const CODE_PATTERN = '/<a href="\.\/\?v=.+?" title="(\S+) {1}/';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rand:crawl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'retrieve best_rated from javlibrary (ng)';
    /**
     * @var array
     */
    private $guzzle_options;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->guzzle_options = [
            'http_errors' => false,
            'timeout'     => config('jlib.timeout'),
            'verify'      => false
        ];
    }

    /**
     * Execute the console command.
     *
     * @param  JavbusService  $service
     * @return int
     */
    public function handle(JavbusService $service)
    {
        /** @var PhpRedisConnection $redis */
        $redis = Redis::connection();

        while (true) {
            $current_list_size = $redis->lLen(self::RAND_LIST);

            if ($current_list_size > config('jlib.rand_list_maxlen')) {
                sleep(4);
                continue;
            }

            $page = rand(1, 6);

            try {
                $res = Http::withOptions($this->guzzle_options)->get(
                    config('jlib.javlibrary_base_url') . '/tw/vl_bestrated.php?list&page=' . $page
                )->body();
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
                continue;
            }

            preg_match_all(self::CODE_PATTERN, $res, $code_match);

            foreach ($code_match[1] as $each_code) {
                try {
                    $res = $service->get_info($each_code);
                } catch (Exception $exception) {
                    $this->error($exception->getMessage());
                    continue;
                }
                $redis->lPush(self::RAND_LIST, json_encode($res));
            }
        }
    }
}
