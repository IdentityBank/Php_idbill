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
# Use(s)                                                                       #
################################################################################

use Exception;

################################################################################
# Class(es)                                                                    #
################################################################################

/**
 * Class BillingIdbBillingClient
 *
 * @package idb\idbill
 */
class BillingIdbBillingClient extends IdbBillingClient
{

    /**
     * @param $response
     *
     * @return |null
     * @throws \Exception
     */
    public function parseResponse($response)
    {
        if ($response == null) {
            throw new Exception('Query returned null');
        }

        $response = json_decode($response, true);

        switch ($response['statusCode']) {
            case 200:
                return json_decode($response['result'], true);
            default:
                return null;
        }
    }

    /**
     * @param $billingName
     *
     * @return \idb\idbill\IdbBillingClient|null
     */
    public static function model($billingName)
    {
        if (!empty($billingName)) {
            return new self($billingName);
        }

        return null;
    }

    /**
     * @param $data
     *
     * @return |null
     * @throws \Exception
     */
    public function createPackage($data)
    {
        $data['active'] = intval($data['active']);
        $query = [
            "query" => "createPackage",
            "data" => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $pid
     * @param $data
     *
     * @return |null
     * @throws \Exception
     */
    public function editPackage($pid, $data)
    {
        $query = [
            "query" => "editPackage",
            "pid" => $pid,
            "data" => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $pid
     *
     * @return |null
     * @throws \Exception
     */
    public function deletePackage($pid)
    {
        $query = [
            "query" => "deletePackage",
            "pid" => $pid
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param null $filterExpression
     * @param null $expressionAttributeNames
     * @param null $expressionAttributeValues
     * @param null $dataTypes
     * @param null $orderByDataTypes
     *
     * @return |null
     * @throws \Exception
     */
    public function findCountAllActionsCosts(
        $filterExpression = null,
        $expressionAttributeNames = null,
        $expressionAttributeValues = null,
        $dataTypes = null,
        $orderByDataTypes = null
    ) {
        $query = [
            "query" => "findCountAllActionsCosts",
            "FilterExpression" => $filterExpression,
            "ExpressionAttributeNames" => $expressionAttributeNames,
            "ExpressionAttributeValues" => $expressionAttributeValues,
            "DataTypes" => $dataTypes,
            "OrderByDataTypes" => $orderByDataTypes,
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
     * @param $data
     *
     * @return |null
     * @throws \Exception
     */
    public function addActionCost($data)
    {
        $query = [
            "query" => "addActionCost",
            "data" => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $id
     * @param $data
     *
     * @return |null
     * @throws \Exception
     */
    public function editActionCost($id, $data)
    {
        $query = [
            "query" => "editActionCost",
            "id" => $id,
            "data" => $data
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param $id
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function deleteActionCost($id)
    {
        $query = [
            "query" => "deleteActionCost",
            "id" => $id
        ];

        return $this->parseResponse($this->execute($query));
    }

    /**
     * @param null $filterExpression
     * @param null $expressionAttributeNames
     * @param null $expressionAttributeValues
     * @param null $dataTypes
     * @param null $orderByDataTypes
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function findCountAllBusinesses(
        $filterExpression = null,
        $expressionAttributeNames = null,
        $expressionAttributeValues = null,
        $dataTypes = null,
        $orderByDataTypes = null
    ) {
        $query = [
            "query" => "findCountAllBusinesses",
            "FilterExpression" => $filterExpression,
            "ExpressionAttributeNames" => $expressionAttributeNames,
            "ExpressionAttributeValues" => $expressionAttributeValues,
            "DataTypes" => $dataTypes,
            "OrderByDataTypes" => $orderByDataTypes,
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
     * @param null $filterExpression
     * @param null $expressionAttributeNames
     * @param null $expressionAttributeValues
     * @param null $dataTypes
     * @param null $orderByDataTypes
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function findCountAllInvoices(
        $filterExpression = null,
        $expressionAttributeNames = null,
        $expressionAttributeValues = null,
        $dataTypes = null,
        $orderByDataTypes = null
    ) {
        $query = [
            "query" => "findCountAllInvoices",
            "FilterExpression" => $filterExpression,
            "ExpressionAttributeNames" => $expressionAttributeNames,
            "ExpressionAttributeValues" => $expressionAttributeValues,
            "DataTypes" => $dataTypes,
            "OrderByDataTypes" => $orderByDataTypes,
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
     * @param null $filterExpression
     * @param null $expressionAttributeNames
     * @param null $expressionAttributeValues
     * @param null $dataTypes
     * @param null $orderByDataTypes
     *
     * @return mixed|null
     * @throws \Exception
     */
    public function findCountAllPayments(
        $filterExpression = null,
        $expressionAttributeNames = null,
        $expressionAttributeValues = null,
        $dataTypes = null,
        $orderByDataTypes = null
    ) {
        $query = [
            "query" => "findCountAllPayments",
            "FilterExpression" => $filterExpression,
            "ExpressionAttributeNames" => $expressionAttributeNames,
            "ExpressionAttributeValues" => $expressionAttributeValues,
            "DataTypes" => $dataTypes,
            "OrderByDataTypes" => $orderByDataTypes,
            "PaginationConfig" =>
                [
                    'Page' => $this->page,
                    'PageSize' => $this->pageSize,
                ]
        ];
        $response = $this->execute($query);

        return $this->parseResponse($response);
    }

}

################################################################################
#                                End of file                                   #
################################################################################
