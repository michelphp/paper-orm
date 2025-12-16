<?php

namespace Test\Michel\PaperORM\Common;

use Michel\PaperORM\Parser\DSNParser;
use Michel\PaperORM\Tools\NamingStrategy;
use Michel\PaperORM\Tools\Slugger;
use Michel\UniTester\TestCase;

class NamingStrategyTest extends TestCase
{

    protected function setUp(): void
    {
        // TODO: Implement setUp() method.
    }

    protected function tearDown(): void
    {
        // TODO: Implement tearDown() method.
    }

    protected function execute(): void
    {
        $tests = [
            'id'                 => 'id',
            'name'               => 'name',
            'emailAddress'       => 'email_address',
            'userId'             => 'user_id',
            'createdAt'          => 'created_at',
            'updatedAt'          => 'updated_at',
            'invoiceNumber'      => 'invoice_number',
            'shippingCost'       => 'shipping_cost',

            'UUID'               => 'uuid',
            'UUIDValue'          => 'uuid_value',
            'userUUID'           => 'user_uuid',
            'HTMLParser'         => 'html_parser',
            'XMLHttpRequest'     => 'xml_http_request',
            'APIResponseCode'    => 'api_response_code',
            'HTTPRequestTime'    => 'http_request_time',
            'PaperORMVersion'    => 'paper_orm_version',

            'user2Id'            => 'user_2_id',
            'version1Name'       => 'version_1_name',
            'HTTP2Server'        => 'http_2_server',
            'Order2Item'         => 'order_2_item',
            'ApiV2Endpoint'      => 'api_v2_endpoint',
            'Invoice2025Count'   => 'invoice_2025_count',

            'User'               => 'user',
            'UserProfile'        => 'user_profile',
            'InvoiceDetail'      => 'invoice_detail',
            'OrderLine'          => 'order_line',
            'ClientAddressBook'  => 'client_address_book',

            'userIDNumber'       => 'user_id_number',
            'userIPAddress'      => 'user_ip_address',
            'userHTTPResponse'   => 'user_http_response',
            'productSKUCode'     => 'product_sku_code',
            'fileMD5Hash'        => 'file_md5_hash',
            'dataJSONEncoded'    => 'data_json_encoded',

            'PaperXMLParser'     => 'paper_xml_parser',
            'PaperURLGenerator'  => 'paper_url_generator',
            'DBConnectionName'   => 'db_connection_name',
            'SQLQueryTime'       => 'sql_query_time',
        ];

        foreach ($tests as $test) {
            $out = NamingStrategy::toSnakeCase($test);
            $this->assertStrictEquals($out, $test);
        }
    }

}
