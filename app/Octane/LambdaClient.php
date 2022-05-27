<?php

namespace App\Octane;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Laravel\Octane\Contracts\Client;
use Laravel\Octane\Contracts\StoppableClient;
use Laravel\Octane\MarshalsPsr7RequestsAndResponses;
use Laravel\Octane\Octane;
use Laravel\Octane\RequestContext;
use Laravel\Octane\OctaneResponse;
use Phambda\Http\Psr\HttpWorker;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class LambdaClient implements Client, StoppableClient
{
    use MarshalsPsr7RequestsAndResponses;

    public function __construct(protected HttpWorker $client)
    {
    }


    public function marshalRequest(RequestContext $context): array
    {
        return [
            $this->toHttpFoundationRequest($context->psr7Request),
            $context,
        ];
    }

    /**
     * Send the response to the server.
     *
     * @param  \Laravel\Octane\RequestContext  $context
     * @param  \Laravel\Octane\OctaneResponse  $octaneResponse
     * @return void
     */
    public function respond(RequestContext $context, OctaneResponse $octaneResponse): void
    {
        if (
            $octaneResponse->outputBuffer &&
            !$octaneResponse->response instanceof StreamedResponse &&
            !$octaneResponse->response instanceof BinaryFileResponse
        ) {
            $octaneResponse->response->setContent(
                $octaneResponse->outputBuffer . $octaneResponse->response->getContent()
            );
        }

        $awsInvocationId = $context->psr7Request->getServerParams()['aws_request_id'];
        $response = $this->toPsr7Response($octaneResponse->response);

        $this->client->respond($awsInvocationId, $response);
    }

    /**
     * Send an error message to the server.
     *
     * @param  \Throwable  $e
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Illuminate\Http\Request  $request
     * @param  \Laravel\Octane\RequestContext  $context
     * @return void
     */
    public function error(Throwable $e, Application $app, Request $request, RequestContext $context): void
    {
        // $this->client->getWorker()->error(Octane::formatExceptionForClient(
        //     $e,
        //     $app->make('config')->get('app.debug')
        // ));
    }

    /**
     * Stop the underlying server / worker.
     *
     * @return void
     */
    public function stop(): void
    {
        // $this->client->getWorker()->stop();
    }
}
