<?php
/**
 * repository error messages
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Contracts;


use Illuminate\Http\Response;


/**
 * Class RepoErrorMessage
 *
 * [code] from your custom biz service
 *
 * @package DONG2020\Contracts
 */
class RestfulErrorMessage
{

    const NotFound = [
        'code'       => '1000',
        'httpStatus' => Response::HTTP_NOT_FOUND,
        'message'    => 'not found'
    ];

    const InvalidRequestPayload = [
        'code'       => '1001',
        'httpStatus' => Response::HTTP_BAD_REQUEST,
        'message'    => 'invalid request payload. please check documentation for request payload',
    ];

    const Unauthorized = [
        'code'       => '1002',
        'httpStatus' => Response::HTTP_UNAUTHORIZED,
        'message'    => 'unauthorized'
    ];

    const Forbidden = [
        'code'       => '1004',
        'httpStatus' => Response::HTTP_FORBIDDEN,
        'message'    => 'forbidden'
    ];

    const MethodNotAllowed = [
        'code'       => '1005',
        'httpStatus' => Response::HTTP_METHOD_NOT_ALLOWED,
        'message'    => 'http method mot allowed'
    ];

    const UnsafeOperation = [
        'code'       => '1006',
        'httpStatus' => Response::HTTP_BAD_REQUEST,
        'message'    => 'Unsafe operation'
    ];

    const InvalidField = [
        'code'       => '1007',
        'httpStatus' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'message'    => 'invalid field'
    ];

    const InvalidQueryParams = [
        'code'       => '1008',
        'httpStatus' => Response::HTTP_BAD_REQUEST,
        'message'    => 'invalid query params'
    ];

    const InvalidJsonHeader = [
        'code'       => '1009',
        'httpStatus' => Response::HTTP_UNPROCESSABLE_ENTITY,
        'message'    => 'http header "Content-Type" should be "application/json"'
    ];

    const InternalServerError = [
        'code'       => '2001',
        'httpStatus' => Response::HTTP_INTERNAL_SERVER_ERROR,
        'message'    => 'internal server error'
    ];

}
