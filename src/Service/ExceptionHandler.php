<?php
/**
 * exception handler
 *
 * PHP Version 7.2
 *
 * @author    v.k
 * @copyright 2019 ec3s.com
 */

namespace DONG2020\Service;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Exceptions\Handler;

use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\HttpException;
use DONG2020\Contracts\RestfulException;


/**
 * exception handler
 */
class ExceptionHandler extends Handler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var \Illuminate\Http\Request|null
     */
    protected $request;

    /**
     * ErrorFormatter constructor.
     *
     * @param bool                     $debug
     * @param \Illuminate\Http\Request $request
     */
    public function __construct(bool $debug, Request $request)
    {
        $this->debug = $debug;
        $this->request = $request;
    }

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     * @throws \Exception
     */
    public function report(\Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Determine if the exception is in the "do not report" list.
     *
     * @param  \Exception $e
     * @return bool
     */
    protected function shouldntReport(\Exception $e)
    {
        $dontReport = parent::shouldntReport($e);
        if ($dontReport) {
            return $dontReport;
        }

        if ($e instanceof RestfulException && $e->getErrorMessage()['httpStatus'] < 500) {
            return true;
        }

        return false;
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Exception               $exception
     * @return \Illuminate\Http\JsonResponse
     */
    public function render($request, \Exception $exception)
    {
        $status = 500;
        $code = '';

        if ($this->debug && app()->environment() != 'testing') {
            $this->renderToConsole($exception);
        }

        $message = $exception->getMessage();

        if ($exception instanceof HttpException) {
            $status = $exception->getStatusCode();
            $message = $message ?: Response::$statusTexts[$status];
        } elseif ($exception instanceof RestfulException) {
            $errorMessage = $exception->getErrorMessage();
            $status = $errorMessage['httpStatus'];
            $message = $exception->getMessage();
            $code = $errorMessage['code'];
        } elseif ($exception instanceof ValidationException) {
            $errorMessages = [];
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $errorMessages[] = $message;
                }
            }
            $message = implode("\n", $errorMessages);
            $status = $exception->status;
        } elseif ($exception instanceof QueryException) {
            if ($exception->getCode() == '23000') {
                $status = Response::HTTP_CONFLICT;
                $message = Response::$statusTexts[$status];
                if ($this->debug) {
                    $message .= ' ' . $exception->getMessage();
                }
            }
        }

        $data = [
            'code'    => $code,
            'status'  => $status,
            'message' => $message
        ];
        if ($this->debug) {
            $data['stack'] = $exception->getTraceAsString();
        } else {
            $data['message'] = $status >= 500 ? Response::$statusTexts[$status] : $message;
        }
        $response = \response()->json($data, $status);
        $response->exception = $exception;
        return $response;
    }

    /**
     * @param \Exception $exception
     */
    protected function renderToConsole($exception)
    {
        error_log(sprintf(
            "%s %s, Exception: %s, Trace: \n%s",
            $this->request->method(),
            $this->request->getRequestUri(),
            $exception->getMessage(),
            $exception->getTraceAsString()
        ), 4);
    }
}