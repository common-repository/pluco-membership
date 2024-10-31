<?php

namespace PLCODashboard\front\classes\paypal\product;

use PayPalHttp\HttpRequest;

class ProductsCreateRequest extends HttpRequest
{
	function __construct()
	{
		parent::__construct("/v1/catalogs/products", "POST");

		$this->headers["Content-Type"] = "application/json";
	}
}
