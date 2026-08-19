<?php

require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('');

try {


	$account = \Stripe\Account::retrieve([
	  'id' => 'acct_1SxqYFCI0vGDm35F',
	  'expand' => ['requirements', 'future_requirements']
	]);

	echo '<pre>';
	print_r($account);
	die;
    $account = \Stripe\Account::retrieve();
	echo '<pre>';
	print_r($account);die;

    if ($account->charges_enabled) {
        echo "Payments Enabled<br>";
    } else {
        echo "Payments Disabled<br>";
    }

    if ($account->payouts_enabled) {
        echo "Payouts Enabled<br>";
    } else {
        echo "Payouts Disabled<br>";
    }

    if (!empty($account->requirements->currently_due)) {

        echo "<br>Missing Requirements:<br>";

        foreach ($account->requirements->currently_due as $item) {
            echo "- " . $item . "<br>";
        }
    }

    if ($account->requirements->disabled_reason) {
        echo "<br>Disabled Reason: " . $account->requirements->disabled_reason;
    }

} catch (\Stripe\Exception\ApiErrorException $e) {

    echo "Error: " . $e->getMessage();
}