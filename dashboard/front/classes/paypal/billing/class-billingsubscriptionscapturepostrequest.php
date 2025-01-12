<?php

namespace PLCODashboard\front\classes\paypal\billing;

use PayPalHttp\HttpRequest;

class BillingSubscriptionsCapturePostRequest extends HttpRequest
{
	function __construct($subscriptionId)
	{
		parent::__construct("/v1/billing/subscriptions/{subscription_id}/capture", "POST");
		$this->path = str_replace("{subscription_id}", urlencode($subscriptionId), $this->path);

		$this->headers["Content-Type"] = "application/json";
	}


}
