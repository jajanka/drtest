<?php

class FileWorker {
    public $cacheTime;
    public $productFile;
    private $products;
    private $header;
    public $readLimit;

    function __construct($fileName, $readLimit)
    {
        $this->cacheTime = time() - 60 * 5;
        $this->productFile = $fileName;
        $this->readLimit = $readLimit;
        $this->products = [];
        $this->header = [];


    }

    public function makeProducts() {
        if (($handle = fopen($this->productFile, "r")) !== FALSE) {
            $currentLine = 0;
            while (($data = fgetcsv($handle, 100000, "\t")) !== FALSE) {
                if ($currentLine > $this->readLimit ) {
                    break;
                }

                $currentLine++;

                if ($currentLine == 1 ) {
                    $this->header = $data;
                    continue;
                }
                $currentLine++;

                $this->products[] = array_combine($this->header , array_slice($data, 0, count( $this->header)));
            }

        }
        fclose($handle);
    }

    public function downloadFile($url, $path)
    {
        if ($this->checkCacheLifetime($path)) {
            ini_set('max_execution_time', 1);
            $curlCh = curl_init();
            curl_setopt($curlCh, CURLOPT_URL, $url);
            curl_setopt($curlCh, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curlCh, CURLOPT_SSLVERSION,3);
            $curlData = curl_exec ($curlCh);
            curl_close ($curlCh);


            $file = fopen($path, "w+");
            fputs($file, $curlData);
            fclose($file);
            return true;
        }
        return false;
    }

    public function checkCacheLifetime($path) {
        return !file_exists($path) || (file_exists($path) && (filemtime($path) > $this->cacheTime));
    }

    public function getProducts() {
        return $this->products;
    }

    public function getProductsHeader() {
        return $this->header;
    }
}

class Product {
    public $productRecord;
    function __construct($productRecord)
    {
        $this->productRecord = $productRecord;
    }


    public function setProduct($productRecord) {
        $this->productRecord = $productRecord;
    }

    public function getId()
    {
        return $this->productRecord['GTIN'];
    }

    public function getName()
    {
        return $this->productRecord['ItemDescription'];
    }

    public function getImageUrl()
    {
        return $this->productRecord['ModelImgXLarge'];
    }
}

class T_shirt extends Product {
    public $productRecord;

    public function __construct($productRecord)
    {
        parent::__construct($productRecord);
    }

    public function getModel()
    {
        return $this->productRecord['ItemNumber'];
    }
}

$fileWorker = new FileWorker('files/test.csv', 100);
if ($fileWorker->downloadFile('http://www.printful.com/test.csv', 'files/test.csv')) {
    echo 'File is downloading please try again later...';
    die();
}

$fileWorker->makeProducts();

$tshirt = new T_shirt($fileWorker->getProducts()[1]);


echo '<table border="1"><tr>';
echo '<th>Product Id</th>';
echo '<th>Product Name</th>';
echo '<th>Image Url</th>';
echo '<th>Model</th>';
echo '</tr>';

foreach ($fileWorker->getProducts() as $item) {
    $tshirt->setProduct($item);
    echo '<tr>';
    echo '<td>' . $tshirt->getId() . '</td>';
    echo '<td>' . $tshirt->getName() . '</td>';
    echo '<td>' . $tshirt->getImageUrl() . '</td>';
    echo '<td>' . $tshirt->getModel() . '</td>';
    echo '</tr>';
}
echo '</table>';
