## [jarbitlira/credomatic-gateway](https://github.com/jarbitlira/credomatic-gateway)

[![Latest Stable Version](https://poser.pugx.org/jarbitlira/credomatic-gateway/v/stable)](https://packagist.org/packages/jarbitlira/credomatic-gateway)
[![Total Downloads](https://poser.pugx.org/jarbitlira/credomatic-gateway/downloads)](https://packagist.org/packages/jarbitlira/credomatic-gateway)
[![License](https://poser.pugx.org/jarbitlira/credomatic-gateway/license)](https://packagist.org/packages/jarbitlira/credomatic-gateway)


## About CredomaticGateway

Credomatic Gateway is a PHP client for **Bac Credomatic** payment service


## Use 

```
$credomaticClient = new CredomaticClient($user_name, $private_key, $public_key);
```
`By default the webService url is already pre configured but just in case that it was changed you can add the fourth param $credomaticWebservice for change to the new url`

If you need to proceess a payment you have to call the processPayment method 

```
$credomaticClient->processPayment($order->getId(), $order->get_total(), $ccNumber, $cvv, $ccexp);
```

For validate you can call the succeeded method 

```
$credomaticClient->succeeded();
```

Additionaly you can get the credomatic gateway response using the getResult method

```
$credomaticClient->getResult();
```
