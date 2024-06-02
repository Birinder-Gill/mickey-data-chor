<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;

class GoelProductController extends Controller
{

    protected $category = [
        "http://goelvetpharma.com/product-category/homeopathic-pet-medicine/",
        "http://goelvetpharma.com/product-category/homeopathic-pet-supplement/",
        "http://goelvetpharma.com/product-category/hen-bird/",
        "http://goelvetpharma.com/product-category/goat-sheep/",
        "http://goelvetpharma.com/product-category/homeopathic-cattle-medicine/",
        "http://goelvetpharma.com/product-category/pet-grooming/",
        "http://goelvetpharma.com/product-category/animal-feed-supplements/",
    ];

    function loadProducts(Request $request) {
        return $this->parseHtml($this->category[0]);
    }

    function parseHtml(string $url,int $page=1) {
        $response = Http::get($url);
        $body = $response->body();
        $dom = new DOMDocument();
        @$dom->loadHTML((string)$body);
        // $elements = $dom->getElementsByTagName('ul');
        // dd($elements[0]);
        // $list = $elements[2];
        // $this->fillList($list,$page);
        $xpath = new DOMXPath($dom);
        

        $products = [];
        $productNodes = $xpath->query('//ul[contains(@class, "products")]/li[contains(@class, "product")]');

        foreach ($productNodes as $node) {
            $hrefNode = $xpath->query('.//a[@class="woocommerce-LoopProduct-link woocommerce-loop-product__link"]', $node)->item(0);
            $href = $hrefNode ? $hrefNode->getAttribute('href') : null;

            $nameNode = $xpath->query('.//h2[@class="woocommerce-loop-product__title"]', $node)->item(0);
            $name = $nameNode ? $nameNode->nodeValue : null;

            $priceNode = $xpath->query('.//span[@class="price"]', $node)->item(0);
            $price = $priceNode ? $priceNode->nodeValue : null;

            $imageNode = $xpath->query('.//img', $node)->item(0);
            $image = $imageNode ? $imageNode->getAttribute('src') : null;

            $products[] = [
                'href' => $href,
                'name' => $name,
                'price' => trim($price),
                'image' => $image
            ];
        }
        $pagination = [];
        $navTags = $dom->getElementsByTagName('nav');
        foreach ($navTags as $nav) {
            if ($nav->getAttribute('class') === 'woocommerce-pagination') {
                $ulTags = $nav->getElementsByTagName('ul');
                foreach ($ulTags as $ul) {
                    if ($ul->getAttribute('class') === 'page-numbers') {
                        $liTags = $ul->getElementsByTagName('li');
                        foreach ($liTags as $li) {
                            $aTags = $li->getElementsByTagName('a');
                            if ($aTags->length > 0) {
                                $pageHref = $aTags->item(0)->getAttribute('href');
                                $pageNumber = $aTags->item(0)->nodeValue;
                                $pagination[] = [
                                    'page' => $pageNumber,
                                    'href' => $pageHref
                                ];
                            } else {
                                $spanTags = $li->getElementsByTagName('span');
                                if ($spanTags->length > 0 && $spanTags->item(0)->getAttribute('class') === 'page-numbers current') {
                                    $pageNumber = $spanTags->item(0)->nodeValue;
                                    $pagination[] = [
                                        'page' => $pageNumber,
                                        'href' => null
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        $uniqueHrefs = [];
        $uniquePagination = [];

        foreach ($pagination as $item) {
            if ($item['href'] !== null && !in_array($item['href'], $uniqueHrefs)) {
                $uniqueHrefs[] = $item['href'];
                $uniquePagination[] = $item;
            }
        }
        return response()->json([
            'products' => $products,
            'pagination' => $uniquePagination
        ]);
    }

 
}


