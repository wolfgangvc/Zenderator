<?php
namespace Zenderator\Abstracts;

use Slim\Http\Request;
use Slim\Http\Response;
use Horizon\Exceptions\TableGatewayException;
use Zend\Db\Adapter\Exception\InvalidQueryException;

abstract class Controller
{

    /** @var \Slim\Container */
    protected $container;
    /** @var AbstractService */
    protected $service;


    public function __construct(\Slim\Container $container)
    {
        $this->container = $container;
        $this->model = $this->service->getNewModelInstance();
    }

    public function listRequest(Request $request, Response $response, $args)
    {
        $objects = [];
        foreach ($this->service->getAll() as $object) {
            $objects[] = $object->__toArray();
        }

        return $this->jsonResponse(
            [
                'Status' => 'OKAY',
                $this->service->getTermPlural() => $objects,
            ],
            $request,
            $response
        );
    }

    public function getRequest(Request $request, Response $response, $args)
    {
        try {
            $object = $this->service->getById($args['id'])->__toArray();

            return $this->jsonResponse(
                [
                    'Status' => 'OKAY',
                    $this->service->getTermSingular() => $object,
                ],
                $request,
                $response
            );
        } catch (TableGatewayException $tge) {
            return $this->jsonResponseException($tge, $request, $response);
        }
    }

    public function createRequest(Request $request, Response $response, $args)
    {
        $newObjectArray = $request->getParsedBody();
        try {
            $object = $this->service->createFromArray($newObjectArray);
            return $this->jsonResponse(
                [
                    'Status' => 'OKAY',
                    $this->service->getTermSingular() => $object->__toArray(),
                ],
                $request,
                $response
            );
        } catch (InvalidQueryException $iqe) {
            return $this->jsonResponseException($iqe, $request, $response);
        }
    }

    public function deleteRequest(Request $request, Response $response, $args)
    {
        try {
            $object = $this->service->getById($args['id'])->__toArray();
            $this->service->deleteByID($args['id']);
            return $this->jsonResponse(
                [
                    'Status' => 'OKAY',
                    $this->service->getTermSingular() => $object,
                ],
                $request,
                $response
            );
        } catch (TableGatewayException $tge) {
            return $this->jsonResponseException($tge, $request, $response);
        }
    }

    public function jsonResponse($json, Request $request, Response $response)
    {
        if (strtolower($json['Status']) != "okay") {
            $response = $response->withStatus(400);
        } else {
            $response = $response->withStatus(200);
        }
        $json['Extra']['Hostname'] = gethostname();
        $json['Extra']['Version'] = phpversion();
        $json['Extra']['TimeExec'] = microtime(true) - APP_START;
        if ($request->hasHeader('Content-type') && $request->getHeader('Content-type')[0] == 'application/json') {
            $response = $response->withJson($json);
            return $response;
        } else {
            $loader = new \Twig_Loader_Filesystem(APP_ROOT .  "/views");
            $twig = new \Twig_Environment($loader);
            $response = $response->getBody()->write($twig->render('api-explorer.html.twig', [
                'page_name' => "API Explorer",
                'json' => $json,
                'json_pretty_printed_rows' => explode("\n", json_encode($json, JSON_PRETTY_PRINT)),
            ]));
            return $response;
        }
    }

    public function jsonResponseException(\Exception $e, Request $request, Response $response)
    {
        return $this->jsonResponse(
            [
                'Status' => 'FAIL',
                'Reason' => $e->getMessage(),
            ],
            $request,
            $response
        );
    }
}
