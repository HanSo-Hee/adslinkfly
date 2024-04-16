<?php

namespace App\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Laminas\Diactoros\Response\RedirectResponse;

class ExternalRedirectShortLinkMiddleware
{
    /** @var int Time out in minutes */
    public $time_out = 5;

    public function __invoke(ServerRequest $request, Response $response, callable $next)
    {
        if ($request->getParam('_name') !== 'short') {
            return $next($request, $response);
        }

        $integration_type = get_option('external_integration_type', 'none');

        if ($integration_type === 'none') {
            return $next($request, $response);
        }

        $access_url = '';
        $secret_key = '';

        if ($integration_type === 'wordpress') {
            $access_url = get_option('wordpress_access_url', '');
            $secret_key = get_option('wordpress_secret_key', '');
        }

        if ($integration_type === 'pressfly') {
            $access_url = get_option('pressfly_access_url', '');
            $secret_key = get_option('pressfly_secret_key', '');
        }

        if (empty($access_url) || empty($secret_key)) {
            return $next($request, $response);
        }

        $alias = $request->getParam('alias');
        $short = Router::url('/' . $alias, true);
        $dataQuery = $request->getQuery('data');
        $typeQuery = $request->getQuery('type');

        try {
            if ($dataQuery) {
                if ($typeQuery !== $integration_type) {
                    throw new \Exception();
                }

                $data = json_decode(external_integration_decrypt(rawurldecode($dataQuery), $secret_key), true);

                if (
                    ($data['status'] === 1) &&
                    ($data['alias'] === $alias) &&
                    (time() - $data['time'] <= $this->time_out * 60)
                ) {
                    return $next($request, $response);
                } else {
                    throw new \Exception();
                }

            } else {
                $data = json_encode([
                    'short' => $short,
                    'alias' => $alias,
                    'status' => 0,
                    'time' => time(),
                ]);

                $data = external_integration_encrypt($data, $secret_key);

                $connector = '?';
                if (strpos($access_url, '?') !== false) {
                    $connector = '&';
                }

                $access_url .= $connector . 'data=' . rawurlencode($data);

                return new RedirectResponse($access_url, 302); // 303
            }

        } catch (\Exception $exception) {
            return new RedirectResponse($short, 302); // 303
        }
    }
}
