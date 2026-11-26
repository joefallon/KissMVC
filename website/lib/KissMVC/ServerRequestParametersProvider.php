<?php declare(strict_types=1);

namespace KissMVC;

final class ServerRequestParametersProvider implements RequestParametersProviderInterface
{
    public function getRequestParameters(): ?array
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = '';

        if($scriptName !== '')
        {
            $scriptDir = dirname($scriptName);
        }

        $request = $requestUri;

        if($this->isConfiguredScriptDirectory($scriptDir))
        {
            $request = str_replace($scriptDir, '', $requestUri);
        }

        $request = trim((string) $request, '/');

        if($request === '')
        {
            return null;
        }

        $requestParameters = $this->urlSegments($request);

        return $requestParameters;
    }

    /**
     * Split the request path into segments and strip query strings.
     *
     * @return array<int, string>
     */
    private function urlSegments(string $request): array
    {
        $requestParams = explode('/', $request);
        $params = [];

        foreach($requestParams as $param)
        {
            if($param === '')
            {
                continue;
            }

            $arr = explode('?', $param, 2);
            $segment = $arr[0] ?? '';

            if($segment !== '')
            {
                $params[] = $segment;
            }
        }

        return $params;
    }

    /**
     * Determine whether a script directory should be removed from the request URI.
     *
     * @param string $scriptDir Directory derived from SCRIPT_NAME.
     * @return bool True when the directory is neither empty nor the root path.
     */
    private function isConfiguredScriptDirectory(string $scriptDir): bool
    {
        $isConfiguredScriptDirectory = ($scriptDir !== '' && $scriptDir !== '/');

        return $isConfiguredScriptDirectory;
    }
}
