# Bold Subscriptions API
#### Laravel Example

Example app for working with the Bold Subscriptions API. This example leverages the [Laravel](https://laravel.com/) PHP framework.

[View Bold third party API documentation](http://docs.boldapps.net/ro-third-party-api/index.html#update-next-order-date)

## Purpose

Bold Subscriptions customers using the **Advanced** plan or higher can use our third party API to interact with subscription data in unique ways. Including the ability to modify, cancel and report on subscriptions.

## Install

1. Clone the repository
2. Install project dependencies

	```console
	$ composer install
	```

3. Configure environment variables

	```console
	$ cp .env.example .env
	```
    
    You will need to set the appropriate values for the following variables:
    
    * **BOLD_API_PRIVATE_KEY** - Private API key created in the Bold Subscriptions app
    * **BOLD_API_HANDLE** - App handle created in the Bold Subscriptions app
    * **MYSHOPIFY_DOMAIN** - The myshopify domain that Bold Subscriptions is installed on (e.g. example-site.myshopify.com)

## Use

As part of this project there is one example console command you can run:

```console
$ php artisan bold:update_next_order_date <shopify_customer_id> <subscription_id> <next_order_date>
```

Replace the following arguments appropriately:

* **<shopify_customer_id>** - The ID of the customer in Shopify
* **<subscription_id>** - The ID of the customer's subscription in Bold Subscriptions
* **<next_order_date>** - The customer's new next order date (e.g. `2019-10-21`)

## Example code explained

#### UpdateNextOrderDate

**File:** `app/Console/Commands/UpdateNextOrderDate.php`

This is a Laravel console command class which describes the command signature and how to handle running it. This command is handled by making a call to the *BoldApiService* `updateNextOrderDate` function.

[View Laravel documentation on writing console commands](https://laravel.com/docs/5.8/artisan#writing-commands)

#### BoldApiService

**File:** `app/Services/BoldApiService.php`

This file is responsible for making an API request to the Bold API. In the **constructor** it will first instantiate a Guzzle Client for making HTTP requests.

[Learn about the Guzzle HTTP Client](http://docs.guzzlephp.org/en/stable/)

In doing so it will bind both a *request handler* and a *response handler* (two types of middleware) to the client. This way before each request a middleware in BoldApiRequestHandler will be called; And before each response the middleware from BoldApiResponseHandler will be called.

When `updateNextOrderDate` is called it will use the Guzzle client and execute a PUT request to the Bold API with the expected data as documented in the third party API documentation.

[View Bold third party API documentation](http://docs.boldapps.net/ro-third-party-api/index.html#update-next-order-date)

#### BoldApiRequestHandler

**File:** `app/Handlers/BoldApiRequestHandler.php`

This file is responsible for ensuring each request has all the appropriate headers  and query parameters before being made. It is also responsible for gaining a temporary authorization code which will be used for subsequent API requests.

The `__invoke` magic method gets called for each request and will do the following:

* Check to see if an auth code has been retrieved yet. If not it will call the `authenticate` function which makes an independent request (not an API request) to retrieve an **auth code** with these requirements:
	* **BOLD-Authorization:** `<private_api_key>` - Required header
	* **handle** - Required query parameter
	* **shop** - Required query parameter
* Once a auth code is retrieved the invoke method will continue by adding the required authorization header and shop query parameter to the API request:
	* **BOLD-Authorization: Bearer** `<auth_code>` - Required header
	* **shop** - Required query parameter

After the request is complete the `__invoke` method of BoldApiResponseHandler is called.

#### BoldApiResponseHandler

**File:** `app/Handlers/BoldApiResponseHandler.php`

Since the authorization code will expire after an amount of time (24 hours, subject to change) you need to ensure you re-authenticate when that happens. This file is responsible for checking if the response from the API was an *Unauthorized* status code (401). If it is then it will automatically re-authenticate and grab a new token for the next request.

**Note:** Since the CLI command is a short process this has no benefit currently. It is merely written for the purpose of learning from this example.

## License

This project is licensed under the MIT License - see the **LICENSE** file for details.

