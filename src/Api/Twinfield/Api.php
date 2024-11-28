<?php
/**
 * API-information: https://accounting.twinfield.com/webservices/documentation/#/ApiReference
 */
namespace AtpCore\Api\Twinfield;

use AtpCore\BaseClass;

class Api extends BaseClass
{
    private \PhpTwinfield\Secure\Provider\OAuthProvider $provider;
    private \PhpTwinfield\Office $office;
    private \PhpTwinfield\Secure\OpenIdConnectAuthentication $connection;

    /**
     * Constructor
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $organisation
     * @param boolean $debug
     */
    public function __construct(
        $clientId, $clientSecret, $redirectUri,
        private readonly string $organisation,
        private readonly bool $debug = false)
    {
        // Reset error-messages
        $this->resetErrors();

        // Set provider
        $this->provider = new \PhpTwinfield\Secure\Provider\OAuthProvider([
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri' => $redirectUri,
        ]);
        $this->office = \PhpTwinfield\Office::fromCode($organisation);
    }

    /**
     * Create dimension
     *
     * @param string $type
     * @param string $code
     * @param string $name
     * @return boolean
     */
    public function createDimension(string $type, string $code, string $name)
    {
        try {
            $connector = new \PhpTwinfield\ApiConnectors\OfficeApiConnector($this->connection);
            $xml = new \Domdocument();
            $dimension = $xml->createElement('dimension');
            $office = $xml->createElement("office", $this->office->getCode());
            $dimension->appendChild($office);
            $type = $xml->createElement("type", $type);
            $dimension->appendChild($type);
            $code = $xml->createElement("code", $code);
            $dimension->appendChild($code);
            $name = $xml->createElement("name", $name);
            $dimension->appendChild($name);
            $xml->appendChild($dimension);
            $res = $connector->sendXmlDocument($xml);
            $res->assertSuccessful();
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e->getTrace());
            return false;
        }

        // Return
        return true;
    }

    /**
     * Create purchase-invoice
     *
     * @param \PhpTwinfield\PurchaseTransactionLine[] $purchaseTransactionLines
     * @return bool
     */
    public function createPurchaseInvoice(string $invoiceNumber, \DateTime $invoiceDate, \DateTime $dueDate, array $purchaseTransactionLines)
    {
        try {
            $connector = new \PhpTwinfield\ApiConnectors\TransactionApiConnector($this->connection);
            $purchaseTransaction = new \PhpTwinfield\PurchaseTransaction();
            $purchaseTransaction->setOffice($this->office);
            $purchaseTransaction->setCode("INK");
            $purchaseTransaction->setDateFromString($invoiceDate->format("Ymd"));
            $purchaseTransaction->setPeriod($invoiceDate->format("Y/m"));
            $purchaseTransaction->setInvoiceNumber($invoiceNumber);
            $purchaseTransaction->setDueDateFromString($dueDate->format("Ymd"));
            $purchaseTransaction->setDestiny(\PhpTwinfield\Enums\Destiny::TEMPORARY());
            $purchaseTransaction->setCurrency(new \Money\Currency("EUR"));
            $purchaseTransaction->setLines($purchaseTransactionLines);
            $res = $connector->send($purchaseTransaction);
        } catch(\Exception $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e->getTrace());
            return false;
        }

        // Return
        return true;
    }

    /**
     * Create sales-invoices
     *
     * @param string $invoiceType
     * @param string $customerId
     * @param \DateTime $invoiceDate
     * @param \PhpTwinfield\InvoiceLine[] $invoiceLines
     * @return bool
     */
    public function createSalesInvoice(string $invoiceType, string $customerId, \DateTime $invoiceDate, \PhpTwinfield\InvoiceTotals $invoiceTotals, array $invoiceLines)
    {
        try {
            $customerConnector = new \PhpTwinfield\ApiConnectors\CustomerApiConnector($this->connection);
            $invoiceConnector = new \PhpTwinfield\ApiConnectors\InvoiceApiConnector($this->connection);
            $invoice = (new \PhpTwinfield\Invoice())
                ->setOffice($this->office)
                ->setInvoiceType($invoiceType)
                ->setStatus("concept")
                ->setCustomer($customerConnector->get($customerId, $this->office))
                ->setBank("BNK")
                ->setPaymentMethod("bank")
                ->setCurrency(new \Money\Currency("EUR"))
                ->setInvoiceDate($invoiceDate->format("Ymd"))
                ->setPeriod($invoiceDate->format("Y/m"))
                ->setDueDateFromString($invoiceDate->add(new \DateInterval("P1M"))->format("Ymd")) // Month after invoice-date
                ->setTotals($invoiceTotals);
            foreach ($invoiceLines as $invoiceLine) {
                $invoice->addLine($invoiceLine);
            }
            $invoiceConnector->send($invoice);
        } catch(\Exception $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e->getTrace());
            return false;
        }

        // Return
        return true;
    }

    /**
     * Get access-token by authorization-code
     *
     * @param string $authorizationCode
     * @return \League\OAuth2\Client\Token\AccessToken|false
     */
    public function getAccessToken(string $authorizationCode)
    {
        try {
            $accessToken = $this->provider->getAccessToken("authorization_code", ["code"=>$authorizationCode]);
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            return false;
        }

        // Return
        return $accessToken;
    }

    /**
     * Get authorization-url
     *
     * @param string $state
     * @return string
     */
    public function getAuthorizationUrl(string $state)
    {
        // Return
        return $this->provider->getAuthorizationUrl(["state"=>$state]);
    }

    /**
     * Get purchase-invoice
     *
     * @param string $invoiceNumber
     * @return false|\PhpTwinfield\BaseTransaction
     */
    public function getPurchaseInvoice(string $invoiceNumber)
    {
        try {
            $connector = new \PhpTwinfield\ApiConnectors\TransactionApiConnector($this->connection);
            $result = $connector->get(\PhpTwinfield\PurchaseTransaction::class, "INK", $invoiceNumber, $this->office);
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e->getTrace());
            return false;
        }

        // Return
        return $result;
    }

    /**
     * Get sales-invoice
     *
     * @param string $invoiceType
     * @param string $invoiceNumber
     * @return false|\PhpTwinfield\Invoice
     */
    public function getSalesInvoice(string $invoiceType, string $invoiceNumber)
    {
        try {
            $connector = new \PhpTwinfield\ApiConnectors\InvoiceApiConnector($this->connection);
            $result = $connector->get($invoiceType, $invoiceNumber, $this->office);
        } catch (\Exception $e) {
            $this->setMessages($e->getMessage());
            $this->setErrorData($e->getTrace());
            return false;
        }

        // Return
        return $result;
    }

    /**
     * Set connection
     *
     * @param \League\OAuth2\Client\Token\AccessToken $accessToken
     * @param string $refreshToken
     */
    public function setConnection(\League\OAuth2\Client\Token\AccessToken $accessToken, string $refreshToken)
    {
        $this->connection = new \PhpTwinfield\Secure\OpenIdConnectAuthentication($this->provider, $refreshToken, $this->office, $accessToken);
    }
}