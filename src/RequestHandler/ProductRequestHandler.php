<?php
/**
 * This file is part of the Billbee Custom Shop API package.
 *
 * Copyright 2019 by Billbee GmbH
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 *
 * Created by Julian Finkler <julian@mintware.de>
 */

namespace Billbee\CustomShopApi\RequestHandler;

use Billbee\CustomShopApi\Exception\ProductNotFoundException;
use Billbee\CustomShopApi\Http\Response;
use Billbee\CustomShopApi\Model\Pagination;
use Billbee\CustomShopApi\Model\Response\GetProductsResponse;
use Billbee\CustomShopApi\Repository\ProductsRepositoryInterface;
use Exception;
use Psr\Http\Message\RequestInterface;

class ProductRequestHandler extends RequestHandlerBase
{
    /** @var ProductsRepositoryInterface */
    private $productsRepository;

    public function __construct(ProductsRepositoryInterface $productsRepository)
    {
        $this->productsRepository = $productsRepository;
        $this->supportedActions = ['GetProduct', 'GetProducts'];
    }

    public function handle(RequestInterface $request, $queryArgs = [])
    {
        if ($queryArgs['Action'] == 'GetProduct') {
            return $this->getProduct($queryArgs);
        }

        if ($queryArgs['Action'] == 'GetProducts') {
            return $this->getProducts($queryArgs);
        }

        return null;
    }

    private function getProduct($queryArgs)
    {
        if (!isset($queryArgs['ProductId']) || empty($productId = trim($queryArgs['ProductId']))) {
            return Response::badRequest('Es wurde keine ProductId übergeben');
        }

        try {
            $product = $this->productsRepository->getProduct($productId);
            return Response::json($product);
        } catch (ProductNotFoundException $e) {
            return Response::notFound('Der Artikel wurde nicht gefunden');
        } catch (Exception $e) {
            return Response::internalServerError($e->getMessage());
        }
    }

    private function getProducts($queryArgs)
    {
        $page = isset($queryArgs['Page']) ? (int)$queryArgs['Page'] : 1;
        $pageSize = isset($queryArgs['PageSize']) ? (int)$queryArgs['PageSize'] : 100;

        try {
            $pagedData = $this->productsRepository->getProducts($page, $pageSize);
            $paging = new Pagination($page, $pageSize, $pagedData->getTotalCount());

            $response = new GetProductsResponse($paging, $pagedData->getData());
            return Response::json($response);
        } catch (Exception $e) {
            return Response::internalServerError($e->getMessage());
        }
    }
}
