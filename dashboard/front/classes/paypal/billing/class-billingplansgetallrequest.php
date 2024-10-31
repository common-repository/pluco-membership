<?php

namespace PLCODashboard\front\classes\paypal\billing;

use PayPalHttp\HttpRequest;

class BillingPlansGetAllRequest extends HttpRequest
{
	function __construct()
	{
		parent::__construct("/v1/billing/plans", "GET");

		$this->headers["Content-Type"] = "application/json";
	}


}
