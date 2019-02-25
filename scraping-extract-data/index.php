<?php 
require __DIR__ . '/vendor/autoload.php';

echo date('Y-m-d H:i:s');
$loop = \React\EventLoop\Factory::create();
$sel = '.menu_test>ul>li>a';
$browser = new \Clue\React\Buzz\Browser($loop);
$scraper = new \AsyncScraper\Scraper($sel, $browser);

// PDO ////// TRUNCATE TABLE [Regions]
// автомат дата = ALTER TABLE Regions ADD CONSTRAINT DF_Regions DEFAULT GETDATE() FOR Date

/*  // Insert EXAMPLE: 
$data = [
    ['ЫЫЫЫ',  '===2'],
    ['ЫЫЫЫЫ',    '===3'],
]; //добавится ток первый, если Name  - ключ;

 //INSERT: 
    $sql = "INSERT INTO Regions ([name],[href]) VALUES (?,?)";
    $data = $query->insert($sql, $data);
*/
/* //SELECT
$sql = "SELECT * FROM [Regions] ORDER BY [Date]";
$data = $query->select($sql);
var_dump($data); die; */

//$urls = [ 'http://www.morflot.ru/portyi_rf/reestr_mp.html' ];
$urls = [ '/portyi_rf/reestr_mp.html' ];

$s = $scraper
    ->scrape(0, ...$urls)
    ->then(function ($e) {
        $ar = $e[0]->hrefs;
        $ports_sql_array = array_map(function (array $obj) {  return [ $obj[0], $obj[1] ] ; }, $ar); //array => ['url1','url2'];

        $sql = "INSERT INTO Regions ([name],[href]) VALUES (?,?)";
        $query = new \AsyncScraper\Queries();
        $data = $query->insert($sql, $ports_sql_array);

        $urls2 = array_map(function (array $obj) { return $obj[1]; }, $ar); //array => ['url1','url2'];
        
        $loop = \React\EventLoop\Factory::create();
        $sel = 'ul.docslist>li>a';
        $browser = new \Clue\React\Buzz\Browser($loop);
        $scraper = new \AsyncScraper\Scraper($sel, $browser);
        $scraper->scrape(0, ...$urls2)->then(function ($e2) {//var_dump($e2[2]);die;

            $ports_arrays = [];
            foreach ($e2 as $obj) {
                    foreach ($obj->hrefs as $obj2) {
                        $port_name = $obj2[0];
                        $port_href = $obj2[1]; // связываем порт с регионом
                        preg_match('/\/\w*\/\w*\/\w*/', $port_href, $out);
                        $regions_href = $out[0].'.html';
                        $ports_arrays[] = [$port_name, $port_href, $regions_href];
                    }
            }
            //var_dump($ports_arrays); die;//array (size=67) => ['url1','url2'];
            
            // ==> SQL 4 ports:
            $ports_query= "INSERT INTO Ports ([name],[href],[regions_href]) VALUES (?,?,?)";
            $query = new \AsyncScraper\Queries();
            $data = $query->insert($ports_query, $ports_arrays);
            
            $urls3 = array_map(function (array $obj) {return $obj[1];}, $ports_arrays); //array => ['url1','url2'];
            
            //var_dump($urls3); //die;//array (size=67) [,,,]
            echo '= urls3';
            $loop = \React\EventLoop\Factory::create();
            $sel = '.sbl>div>p';
            $browser = new \Clue\React\Buzz\Browser($loop);
            $scraper = new \AsyncScraper\Scraper($sel, $browser);
            $scraper->scrape(0, ...$urls3)->then(function ($e3) {
               // var_dump($e3); 
               echo 666;
                die;
                
                $docs_arrays = [];
                foreach ($e3 as $obj) {
                        foreach ($obj->hrefs as $obj2) {
                            $port_name = $obj2[0];
                            $port_href = $obj2[1]; // связываем порт с регионом
                            //preg_match('/\/\w*\/\w*\/\w*/', $port_href, $out);
                            //$regions_href = $out[0].'.html';
                            $docs_arrays[] = [$port_name, $port_href];
                        }
                }
            var_dump($docs_arrays); die;//array (size=67) => ['url1','url2'];

            
            });


            echo 'out';

        });
        $loop->run();

    });
$loop->run(); 
