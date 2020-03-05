<?php
# * ********************************************************************* *
# *                                                                       *
# *   Driver for IDBill                                                   *
# *   This file is part of idbill. This project may be found at:          *
# *   https://github.com/IdentityBank/Php_idbill.                         *
# *                                                                       *
# *   Copyright (C) 2020 by Identity Bank. All Rights Reserved.           *
# *   https://www.identitybank.eu - You belong to you                     *
# *                                                                       *
# *   This program is free software: you can redistribute it and/or       *
# *   modify it under the terms of the GNU Affero General Public          *
# *   License as published by the Free Software Foundation, either        *
# *   version 3 of the License, or (at your option) any later version.    *
# *                                                                       *
# *   This program is distributed in the hope that it will be useful,     *
# *   but WITHOUT ANY WARRANTY; without even the implied warranty of      *
# *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the        *
# *   GNU Affero General Public License for more details.                 *
# *                                                                       *
# *   You should have received a copy of the GNU Affero General Public    *
# *   License along with this program. If not, see                        *
# *   https://www.gnu.org/licenses/.                                      *
# *                                                                       *
# * ********************************************************************* *

################################################################################
# Namespace                                                                    #
################################################################################

namespace idb\idbill;

################################################################################
# Include(s)                                                                   #
################################################################################

include_once 'simplelog.inc';

################################################################################
# Use(s)                                                                       #
################################################################################

use DateTime;
use Exception;
use idbyii2\helpers\Localization;
use xmz\simplelog\SimpleLogLevel;
use xmz\simplelog\SNLog as Log;
use function xmz\simplelog\registerLogger;

################################################################################
# Class(es)                                                                    #
################################################################################

abstract class IdbBillingClient
{

    private static $logPath = "/var/log/p57b/";
    protected $billingName = null;
    protected $page = null;
    protected $pageSize = 25;
    private $errors = [];
    private $maxBufferSize = 4096;
    private $host = null;
    private $port = null;
    private $configuration = null;

    /* ---------------------------- General --------------------------------- */

    /**
     * IdbBillingClient constructor.
     *
     * @param $billingName
     */
    public function __construct($billingName)
    {
        $this->billingName = $billingName;
    }

    /**
     * @param $billingName
     *
     * @return \idb\idbill\IdbBillingClient|null
     */
    public static abstract function model($billingName);

    /**
     * @param $host
     * @param $port
     */
    public function setConnection($host, $port, $configuration = null)
    {
        $this->host = $host;
        $this->port = $port;
        $this->configuration = $configuration;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param $pageSize
     *
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        if (!empty($pageSize) && is_int($pageSize) && ($pageSize > 0)) {
            $this->pageSize = $pageSize;
        }

        return $this;
    }

    /**
     * @param $page
     *
     * @return $this
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * @param      $page
     * @param null $pageSize
     *
     * @return $this
     */
    public function setPagination($page, $pageSize = null)
    {
        $this->page = $page;
        if (!empty($pageSize) && is_int($pageSize) && ($pageSize > 0)) {
            $this->pageSize = $pageSize;
        }

        return $this;
    }

    /**
     * @param array $orderByDataTypes
     * @param null  $filter
     *
     * @return mixed
     *
     */
    public function getCountAllPackages($filter = null, $orderByDataTypes = ['order' => 'ASC'])
    {
        $query = [
            "OrderByDataTypes" => $orderByDataTypes,
            'filter' => $filter,
            'query' => "getCountAllPackages"
        ];

        return $this->parseResponse($this->execute($query));
    }

    /* --------------------------- Billing API ------------------------------ */

    public abstract function parseResponse($response);

    /**
     * @param $query
     *
     * @return bool|string|null
     */
    protected function execute($query)
    {
        try {
            $this->logRequestQuery($query);

            $query = json_encode($query);
            if (!empty($this->host)) {
                $this->host = gethostbyname($this->host);
            }

            if (empty($query) || empty($this->host) || empty($this->port)) {
                return null;
            }

            // ***
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            // ***

            if ($socket === false) {
                $this->errors[] = "socket_create() failed: reason: " . socket_strerror(socket_last_error());
            }

            // ***
            $result = socket_connect($socket, $this->host, $this->port);
            // ***

            if ($result === false) {
                $this->errors[] = "socket_connect() failed. Reason: ($result) " . socket_strerror(
                        socket_last_error($socket)
                    );
            }

            if (empty($this->configuration['Security'])) {
                $queryResult = $this->executeRequestNone($socket, $query);
            } elseif (strtoupper($this->configuration['Security']['type']) === 'TOKEN') {
                $queryResult = $this->executeRequestToken($socket, $query);
            } elseif (strtoupper($this->configuration['Security']['type']) === 'CERTIFICATE') {
                $queryResult = $this->executeRequestCertificate($socket, $query);
            } else {
                $queryResult = $this->executeRequestNone($socket, $query);
            }

            // ***
            socket_close($socket);
            // ***

            if (empty($this->errors)) {
                return $queryResult;
            }
        } catch (Exception $e) {
            error_log('Problem processing your query.');
            error_log(json_encode(['host' => $this->host, 'port' => $this->port]));
            if (!empty($e) and !empty($e->getMessage())) {
                error_log($e->getMessage());
            }
        }

        return null;
    }

    /**
     * @param $query
     */
    private function logRequestQuery($query)
    {
        if (SimpleLogLevel::get() < SimpleLogLevel::DEBUG) {
            return;
        }
        $logName = "p57b.idbill.query.log";
        $logPath = self::$logPath . $logName;
        registerLogger($logName, $logPath);

        $pid = getmypid();
        Log::debug(
            $logName,
            "$pid - " .
            "[Q|" . json_encode($query) . "]"
        );
    }

    /**
     * @param $socket
     * @param $query
     *
     * @return string|null
     */
    public function executeRequestNone($socket, $query)
    {
        $queryResult = null;
        try {
            socket_write($socket, $query, strlen($query));

            $queryResult = '';
            while ($result = socket_read($socket, $this->maxBufferSize)) {
                $queryResult .= $result;
            }
        } catch (Exception $e) {
            $queryResult = null;
        }

        return $queryResult;
    }

    /**
     * @param $socket
     * @param $query
     *
     * @return bool|string|null
     */
    public function executeRequestToken($socket, $query)
    {
        $queryResult = null;
        if (!empty($this->configuration['socketTimeout'])) {
            socket_set_option(
                $socket,
                SOL_SOCKET,
                SO_RCVTIMEO,
                ['sec' => $this->configuration['socketTimeout'], 'usec' => 0]
            );
            socket_set_option(
                $socket,
                SOL_SOCKET,
                SO_SNDTIMEO,
                ['sec' => $this->configuration['socketTimeout'], 'usec' => 0]
            );
        }
        try {

            $dataChecksum = md5($query);
            $dataLength = strlen($query);
            $dataChecksumLength = strlen($dataChecksum);
            $size = $dataLength + $dataChecksumLength;
            $size = pack('P', $size);
            $protocolVersion = 1;
            $protocolVersion = pack('V', $protocolVersion);
            $token = $this->configuration['Security']['token'];
            $token = str_pad($token, $this->configuration['Security']['tokenSizeBytes'], " ", STR_PAD_RIGHT);
            $id = time();
            $id = pack('P', $id);
            $dataChecksumType = str_pad('MD5', 8);

            socket_write($socket, $protocolVersion, strlen($protocolVersion));
            socket_write($socket, $token, strlen($token));
            socket_write($socket, $size, strlen($size));
            socket_write($socket, $id, strlen($id));
            socket_write($socket, $dataChecksumType, strlen($dataChecksumType));
            socket_write($socket, $dataChecksum, strlen($dataChecksum));
            socket_write($socket, $query, strlen($query));

            $version = '';
            while ($result = socket_read($socket, 4)) {
                $version .= $result;
                if (4 <= strlen($version)) {
                    break;
                }
            }
            if (!empty($version)) {
                $version = unpack('V', $version);
                $version = intval($version);
            }
            if ($version != 1) {
                return null;
            }
            $token = '';
            while ($result = socket_read($socket, $this->configuration['Security']['tokenSizeBytes'])) {
                $token .= $result;
                if ($this->configuration['Security']['tokenSizeBytes'] <= strlen($token)) {
                    break;
                }
            }
            $size = '';
            while ($result = socket_read($socket, 8)) {
                $size .= $result;
                if (8 <= strlen($size)) {
                    break;
                }
            }
            if (!empty($size)) {
                $size = unpack('P', $size);
            }
            $id = '';
            while ($result = socket_read($socket, 8)) {
                $id .= $result;
                if (8 <= strlen($id)) {
                    break;
                }
            }
            if (!empty($id)) {
                $id = unpack('P', $id);
            }
            $checksumType = '';
            while ($result = socket_read($socket, 8)) {
                $checksumType .= $result;
                if (8 <= strlen($checksumType)) {
                    break;
                }
            }
            $checksumType = trim($checksumType);

            $queryResult = '';
            while ($result = socket_read($socket, $this->maxBufferSize)) {
                $queryResult .= $result;
                if ($size <= strlen($queryResult)) {
                    break;
                }
            }
            $checksum = substr($queryResult, 0, 32);
            $queryResult = substr($queryResult, 32);
            if (strtoupper($checksumType) === 'MD5') {
                $dataChecksum = md5($queryResult);
            } else {
                $dataChecksum = null;
            }
            if (strtolower($checksum) !== $dataChecksum) {
                $queryResult = null;
            }
        } catch (Exception $e) {
            $queryResult = null;
        }

        return $queryResult;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getPackage($id)
    {
        $query = [
            "FilterExpression" => [
                "o" => "=",
                "l" => "#id",
                "r" => ":id"
            ],
            "ExpressionAttributeNames" => ["#id" => "id"],
            "ExpressionAttributeValues" => [":id" => "$id"],
            'query' => 'getPackages'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param array $data
     *
     * @return string|null
     */
    public function createInvoice($data)
    {
        $query = [
            'data' => $data,
            'query' => 'createInvoice'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param array $data
     *
     * @return string|null
     */
    public function logPayment($data)
    {
        $query = [
            'data' => $data,
            'query' => 'logPayment'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @return mixed
     */
    public function getLastInvoiceNumber()
    {
        $query = [
            'query' => 'getLastInvoiceNumber'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $oid
     *
     * @return mixed
     */
    public function getPaymentsForOrganization($oid)
    {
        $query = [
            "query" => "getPaymentsForOrganization",
            "oid" => $oid,
            "PaginationConfig" =>
                [
                    'Page' => $this->page,
                    'PageSize' => $this->pageSize,
                ]
        ];
        $response = $this->execute($query);

        return $this->parseResponse($response);
    }

    /**
     * @param $expression
     * @param $colNames
     * @param $colValues
     *
     * @return mixed
     */
    public function findInvoices($expression, $colNames, $colValues)
    {
        $query = [
            "query" => 'findInvoices',
            "FilterExpression" => $expression,
            "ExpressionAttributeNames" => $colNames,
            "ExpressionAttributeValues" => $colValues,
            "PaginationConfig" => [
                'Page' => $this->page,
                'PageSize' => $this->pageSize,
            ]
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param      $paymentId
     * @param null $oid
     *
     * @return mixed
     */
    public function getInvoiceForPayment($paymentId, $oid = null)
    {
        $query = [
            "query" => "getInvoiceForPayment",
            "paymentId" => $paymentId,
            "oid" => $oid,
            "PaginationConfig" => [
                'Page' => $this->page,
                'PageSize' => $this->pageSize,
            ]
        ];
        $response = $this->execute($query);

        return $this->parseResponse($response);
    }

    /**
     * @param      $organizationId
     * @param null $orderByDataTypes
     *
     * @return mixed
     */
    public function getCountAllBillingAuditLogsForBusiness(
        $organizationId,
        $orderByDataTypes = ["timestamp" => "DESC", "id" => "DESC"]
    ) {
        $query = [
            "query" => "getCountAllBillingAuditLogsForBusiness",
            "oid" => $organizationId,
            "OrderByDataTypes" => $orderByDataTypes,
            "PaginationConfig" =>
                [
                    'Page' => $this->page
                ]
        ];
        $response = $this->execute($query);

        return $this->parseResponse($response);
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function addBillingAuditLog($data)
    {
        $query = [
            'data' => $data,
            'query' => 'addBillingAuditLog'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function addBusiness($data)
    {
        $query = [
            "query" => "addBusiness",
            "data" => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @return mixed
     */
    public function findAllBusinessPackages()
    {
        $query = [
            'query' => 'findCountAllBusinessPackage'
        ];

        return $this->parseResponse($this->execute($query));
    }

    /* ---------------------------- Internal -------------------------------- */

    /**
     * @param $organizationId
     *
     * @return mixed
     */
    public function getBusinessPackage($organizationId)
    {
        $query = [
            "query" => "getBusinessPackage",
            "businessId" => $organizationId
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public function assignPackageToBusiness($data)
    {
        $query = [
            "query" => "assignPackageToBusiness",
            "data" => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $id
     * @param $data
     *
     * @return mixed
     */
    public function updateBusinessPackage($id, $data)
    {
        $query = [
            "query" => "updateBusinessPackage",
            "id" => $id,
            'data' => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * get outdated "BusinessPackage".
     * Method use "findCountAllBusinessPackage" query under the hood,
     * with some additional filters.
     *
     * @return mixed
     * @throws Exception
     */
    public function getOutdatedBusinessPackages()
    {
        $query = [
            "query" => "findCountAllBusinessPackage",
            "FilterExpression" => [
                "o" => "<=",
                "l" => "#next_payment",
                "r" => ":next_payment"
            ],
            "ExpressionAttributeNames" => ["#next_payment" => "next_payment"],
            "ExpressionAttributeValues" => [":next_payment" => Localization::getDatabaseDateTime(new DateTime())],

        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getBusinessPackageByPackage($id)
    {
        $query = [
            "query" => "findCountAllBusinessPackage",
            "FilterExpression" => [
                "o" => "=",
                "l" => "#id",
                "r" => ":id"
            ],
            "ExpressionAttributeNames" => ["#id" => "package_id"],
            "ExpressionAttributeValues" => [":id" => $id],

        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param array $orderByDataTypes
     *
     * @return mixed
     */
    public function getCountAllActivePackages($orderByDataTypes = ['order' => 'ASC'])
    {
        $query = [
            "OrderByDataTypes" => $orderByDataTypes,
            "FilterExpression" => [
                "o" => "=",
                "l" => "#active",
                "r" => ":active"
            ],
            "ExpressionAttributeNames" => ["#active" => "active"],
            "ExpressionAttributeValues" => [":active" => 1],
            'query' => "getCountAllPackages"
        ];

        return $this->parseResponse($this->execute($query));
    }
}

################################################################################
#                                End of file                                   #
################################################################################
