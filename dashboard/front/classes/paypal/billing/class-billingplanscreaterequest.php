<?php

namespace PLCODashboard\front\classes\paypal\billing;

use PayPalHttp\HttpRequest;

class BillingPlansCreateRequest extends HttpRequest
{
	function __construct()
	{
		parent::__construct("/v1/billing/plans", "POST");

		$this->headers["Content-Type"] = "application/json";
	}


}
