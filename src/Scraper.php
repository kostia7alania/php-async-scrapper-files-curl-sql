<?php namespace AsyncScraper;

libxml_use_internal_errors(true);
ini_set('max_execution_time', 0);
ini_set('max_input_time', 0);
set_time_limit(0);

use Clue\React\Buzz\Browser;
use function React\Promise\all;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\DomCrawler\Crawler;

final class Scraper
{ 
    private $browser;
    private $URL = 'http://www.morflot.ru';

    public function __construct(Browser $browser)
    { 
        $this->browser = $browser;
    }
    

    public function scrape($stage = 0, ...$urls): PromiseInterface
    {
            $promises = array_map(function ($url) {
                return $this->extractFromUrl( is_array($url) ? $url[1] : $url );
            }, $urls);
            return all($promises);
    }

    private function extractFromUrl($url): PromiseInterface
    {
        return $this->browser->get($this->URL . $url)->then(
            function (ResponseInterface $response) {
                return $this->extract((string) $response->getBody());
            },
            function (Exception $error) { var_dump('There was an error', $error->getMessage()); }
        );
    }

    private function extract(string $responseBody)
    {
        $crawler = new Crawler($responseBody);
       
            $source = $crawler->filter('.menu_test>ul>li>a');
            $hrefs = $source->extract(['_text', 'href']);
            if (!empty($hrefs)) {//первая стадия - парсим регионы
                $hrefs = $source->extract(['_text', 'href']); //->extract(['_text']);//->attr('href');
                return $this->regionsHandler($hrefs); //var_dump($hrefs);die;
            }  

            $source = $crawler->filter('ul.docslist>li>a');
            $hrefs = $source->extract(['_text', 'href']);
            if (!empty($hrefs)) {
                foreach ($crawler->filter('ul.docslist>li>font') as $k=>$domElement) {
                    $hrefs[$k][] = $domElement->nodeValue;
                }
                return $this->portsHandler($hrefs);
            }
            
            $h2 = $crawler->filter('.sbl>div>h2')->extract(['_text'])[0];
            if (!empty($h2)) {
                $doc_href = $crawler->filter('.sbl>div>p>a')->extract(['href'])[0];
                $doc_declared_size = $crawler->filter('.sbl>div>p>font')->extract(['_text'])[0];
                //$hrefs = $source
                echo '<hr>';
                $doc = [$h2, $doc_href, $doc_declared_size];
                return $this->goCurl($doc);
            }
        echo 'Error.!. Don\'t find the right selector';
        //$ret = new Image(...$hrefs); //hrefs => Array!!
        return 'it\'s all';
    }


 
    private function goCurl($doc) {
       // sleep(10);
        $ch = curl_init( $this->URL . $doc[1] );  /* create URL handler */
        curl_setopt($ch, CURLOPT_NOBODY, TRUE); /* don't retrieve body contents */
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE); /* follow redirects */
        curl_setopt($ch, CURLOPT_HEADER, FALSE); /* retrieve last modification time */
        curl_setopt($ch, CURLOPT_FILETIME, TRUE); /* get timestamp */
        $res = curl_exec($ch);
        $timestamp = curl_getinfo($ch, CURLINFO_FILETIME);
        curl_close($ch);
        $doc[3] = gmdate("Y-m-d H:i:s", $timestamp); 
        $doc[4] = md5_file($this->URL . $doc[1]); // with file downloading;
        var_dump($doc);
        return $this->DocsSql([$doc]);
    }
  


    private function regionsHandler ($regions)
    {
            $sql = "INSERT INTO Regions ([name],[href]) VALUES (?,?)";
            $query = new \AsyncScraper\Queries();
            $data = $query->insert($sql, $regions);
            //var_dump($regions);die; // array (size=8)[[name,url],[name,url],..]
            //$this->scrapePorts($regions);
            return $this->scrape(1, ...$regions);
    }
    

    private function portsHandler ($portsArrays)
    {
            $portsQuery= "INSERT INTO Ports ([name], [href], [text]) VALUES (?,?,?)";
            $query = new \AsyncScraper\Queries();
            $data = $query->insert($portsQuery, $portsArrays); 
            return $this->scrape(1, ...$portsArrays);
    }


    private function DocsSql($doc): PromiseInterface
    {
        $sql = "INSERT INTO [Documents] ([name],[href],[declared_size], [Filetime], [Hash]) VALUES (?,?,?,?,?)";
        $query = new \AsyncScraper\Queries();
        
        $data = $query->insert($sql, $doc);
         
        //var_dump($regions);die; // array (size=8)[[name,url],[name,url],..]
        //$this->scrapePorts($regions);
        echo '<h1 style="color:red;font-size:11;">['.date('Y-m-d H:i:s').']: Thead FINISH</h1>';
        return 'sukes.!.';
    }



}
