<?php

namespace PLCODashboard\front\classes\paypal\billing;

use PayPalHttp\HttpRequest;

class BillingSubscriptionsCreateRequest extends HttpRequest
{
	function __construct()
	{
		parent::__construct("/v1/billing/subscriptions", "POST");

		$this->headers["Content-Type"] = "application/json";
	}


}
