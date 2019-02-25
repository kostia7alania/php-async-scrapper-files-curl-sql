<?php 
namespace AsyncScraper;
    final class Image
    {
    public $hrefs;
    public function __construct (array ...$hrefs) {
        $this->hrefs = $hrefs;
    }
 
}
