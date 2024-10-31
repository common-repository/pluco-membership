<?php

namespace PLCODashboard\front\classes\paypal\billing;

use PayPalHttp\HttpRequest;

class BillingSubscriptionTransactionsGetRequest extends HttpRequest
{
	function __construct($subscriptionId, $startTime, $endTime)
	{
		parent::__construct("/v1/billing/subscriptions/{subscription_id}/transactions?", "GET");
		$this->path = str_replace("{subscription_id}", urlencode($subscriptionId), $this->path);
		$this->path .= 'start_time=' . $startTime;
		$this->path .= '&end_time=' . $endTime;

		$this->headers["Content-Type"] = "application/json";
	}
}
