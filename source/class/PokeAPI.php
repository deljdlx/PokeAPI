<?php

namespace DelJDLX\PokeAPI;

class Client
{

    protected $apiRootURL = 'https://pokeapi.co/api/v2';
    protected $userAgent =  'jdlx-http-client';
    protected $imageRoottURL = 'https://img.pokemondb.net/artwork/';

    protected $cachePath = __DIR__ . '/../../data';
    protected $picturePath = __DIR__. '/../../image';

    protected $endPoints = [
        'type' => '/type',
        'pokemon' => '/pokemon',
        'species' => '/pokemon-species',
    ];


    private $verbose;

    public function __construct($verbose = true)
    {
        $this->verbose = $verbose;
    }



    public function getSpecies() {

        $items = [];

        $endPoint = $this->endPoints['species'];
        do {
            $list = json_decode($this->request('GET', $endPoint));


            foreach($list->results as $data) {

                $this->log($data->url);

                $itemData = json_decode($this->request('GET', $this->getEndpointFromURL($data->url), null, [], $this->cachePath . '/species'));

                $this->log($itemData->name);

                $items[] = $itemData;
            }

            if($list->next) {
                $endPoint = $this->getEndpointFromURL($list->next);
            }
            else {
                $endPoint = null;
            }

        } while($endPoint);
        

        return $items;
    }





    public function getTypes() {

        $typeList = json_decode($this->request('GET', $this->endPoints['type'], null, [], $this->cachePath . '/list'));
        $types = [];
        foreach($typeList->results as $data) {
            $typeData = json_decode($this->request('GET', $this->getEndpointFromURL($data->url), null, [], $this->cachePath . '/type'));
            $types[] = $typeData;
        }
        return $types;
    }


    public function getPokemons() {
        $pokemons = [];

        $endPoint = $this->endPoints['pokemon'];
        do {
            $pokemonList = json_decode($this->request('GET', $endPoint, null, [], $this->cachePath . '/pokemon'));

            
            foreach($pokemonList->results as $data) {
                $pokemonData = json_decode($this->request('GET', $this->getEndpointFromURL($data->url), null, [], $this->cachePath . '/pokemon'));

                $this->log($pokemonData->order . "\t" . $data->name);

                $this->downloadPicture($pokemonData);
                $pokemons[] = $pokemonData;
            }

            if($pokemonList->next) {
                $endPoint = $this->getEndpointFromURL($pokemonList->next);
            }
            else {
                $endPoint = null;
            }

        } while($endPoint);
        

        return $pokemons;
    }


    public function downloadPicture($data)
    {
        $destination = $this->picturePath . '/artwork/' . $data->id.'-' . $data->name .'.jpg';
        if(is_file($destination)) {
            return;
        }
        $this->log($this->imageRoottURL . '/' . $data->name . '.jpg');
        $image = file_get_contents($this->imageRoottURL . '/' . $data->name . '.jpg');
        if($image) {
            file_put_contents($destination, $image);
        }
    }



    public function request($method, $endPoint, $data = null, $headers = [], $cachePath = null)
    {
        if($cachePath) {
            $cacheFile = $cachePath . '/' . $this->slugify($endPoint) . '.json';
        }
        else {
            $cacheFile = $this->cachePath . '/' . $this->slugify($endPoint) . '.json';
        }
        
        if(is_file($cacheFile)) {
            return file_get_contents($cacheFile);
        }
        
        $options = array(
            'http' => array( 
                'header' =>
                    "User-Agent: ".$this->userAgent."\r\n"
                ,
                'method' => $method,
            )
        );
    
        foreach($headers as $name => $value) {
            $options['http']['header'] .= $name.': ' . $value ."\r\n";
        }
    
        if($data !== null) {
            $options['http']['content'] = $data;
            $options['http']['header'] .= 'Content-Length: ' . strlen($data) ."\r\n";
        }
    
        $context = stream_context_create($options);
        
        $url = $this->apiRootURL . $endPoint;
        $buffer = file_get_contents($url,false, $context);
        file_put_contents($cacheFile, $buffer);
        return $buffer;
    }


    public function slugify($text) {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }


    
    public function getEndpointFromURL($url)
    {
        return str_replace($this->apiRootURL, '', $url);
    }
    
    private function log($data) {
        if($this->verbose) {
            echo $data . PHP_EOL;
        }
    }
}
