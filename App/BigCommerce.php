<?php

namespace App;

use App\Logger;

class BigCommerce extends Controller {
    private $store_hash;
    private $api_url = 'https://api.bigcommerce.com/stores/';
    private $x_auth_token;

    public function __construct(string $store_hash, string $x_auth_token)
    {
        $logger = new Logger();
        parent::__construct($logger);
        $this->store_hash = $store_hash;
        $this->x_auth_token = $x_auth_token;
    }

    /**
     * Get Products
     * Get all products from BigCommerce
     * 
     * @return array
     */
    public function getProducts($page = 1, $limit = 250): array
    {
        $getAllProducts = $this->curl(
            $this->api_url . $this->store_hash . '/v3/catalog/products?page=' . $page . '&limit=' . $limit,
            'GET',
            [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Content-Type' => 'application/json',
                'X-Auth-Token' => $this->x_auth_token
            ]
        );

        return $getAllProducts;
    }

    /**
     * Get Product Modifiers
     * Get all product modifiers from BigCommerce
     * 
     * @return array
     */
    public function getProductModifiers($product_id, $page = 1, $limit = 250): array
    {
        $getAllProducts = $this->curl(
            $this->api_url . $this->store_hash . '/v3/catalog/products/' . $product_id . '/modifiers?page=' . $page . '&limit=' . $limit,
            'GET',
            [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Content-Type' => 'application/json',
                'X-Auth-Token' => $this->x_auth_token
            ]
        );

        return $getAllProducts;
    }

    /**
     * Get Product Variants
     * Get all product variants from BigCommerce
     * 
     * @return array
     */
    public function getProductVariants($product_id, $page = 1, $limit = 250): array
    {
        $getAllProducts = $this->curl(
            $this->api_url . $this->store_hash . '/v3/catalog/products/' . $product_id . '/variants?page=' . $page . '&limit=' . $limit,
            'GET',
            [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Content-Type' => 'application/json',
                'X-Auth-Token' => $this->x_auth_token
            ]
        );

        return $getAllProducts;
    }

    /**
     * Get Promotions
     * Get all promotions from BigCommerce
     * 
     * @param string $name The name of the promotion
     * 
     * @return array
     */
    public function getPromotions(string $name): array
    {
        return $this->curl(
            $this->api_url . $this->store_hash . '/v3/promotions?page=1&limit=1&name=' . urlencode($name),
            'GET',
            [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip, deflate, br',
                'Content-Type' => 'application/json',
                'X-Auth-Token' => $this->x_auth_token
            ]
        );
    }
}
