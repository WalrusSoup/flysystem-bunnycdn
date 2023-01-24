<?php

namespace PlatformCommunity\Flysystem\BunnyCDN;

use DateTimeInterface;

class BunnyPullZone
{
    protected string $pullzoneUrl;
    protected string $pullzoneSecurityToken;

    public function __construct(string $pullzoneUrl, string $pullzoneSecurityToken = '')
    {
        $this->pullzoneUrl = $pullzoneUrl;
        $this->pullzoneSecurityToken = $pullzoneSecurityToken;
    }

    public function publicUrl(string $path): string
    {
        return rtrim($this->pullzoneUrl, '/').'/'.ltrim($path, '/');
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt, array $urlParameters = [], string $allowForPath = ''): string
    {
        // bunny requires params in ascending order
        ksort($urlParameters);
        $queryParameters = http_build_query($urlParameters);
        $expiration = $expiresAt->getTimestamp();
        $basePathToHash = $this->pullzoneSecurityToken . $path . $expiresAt->getTimestamp() . $queryParameters;
        $hash = hash('sha256', $basePathToHash, true);
        // per docs, assemble and strip extra characters out
        $token = base64_encode($hash);
        $token = strtr($token, '+/', '-_');
        $token = str_replace('=', '', $token);
        // original params can be added after the token, but before expires
        if(!empty($queryParameters)) {
            $queryParameters = '&' . $queryParameters;
        }
        // assemble the final path and merge it back with the pullzone_url
        $signedPath =  "?token={$token}{$queryParameters}&expires={$expiration}";

        return rtrim($this->pullzoneUrl, '/') . $path . $signedPath;
    }
}