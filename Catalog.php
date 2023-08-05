<?php

namespace ItIsAllMail\Driver;

use ItIsAllMail\Interfaces\CatalogDriverInterface;
use ItIsAllMail\DriverCommon\AbstractCatalogDriver;
use ItIsAllMail\HtmlToText;
use ItIsAllMail\CoreTypes\SerializationMessage;
use ItIsAllMail\Utils\Browser;
use ItIsAllMail\Utils\Debug;
use ItIsAllMail\Utils\URLProcessor;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;

class RedditCatalogDriver extends AbstractCatalogDriver implements CatalogDriverInterface
{
    protected string $driverCode = "reddit.com";

    public function queryCatalog(string $query, array $opts = []): array
    {
        $normalizedQuery = $this->normalizeQuery($query);
        $json = Browser::getAsString($normalizedQuery);
        $prePosts = json_decode($json, true);
        $result = [];
        $codePostfix = '@' . $this->getCode();

        foreach ($prePosts['data']['children'] as $post) {
            $post = $post['data'];

            $id = $post['id'];
            $from = $post['author'];
            $created = (new \DateTime())->setTimestamp($post['created_utc']);
            $url = 'https://' . $this->getCode() . $post['permalink'];
            $parent = null;
            $threadId = $id;
            $body = strlen($post['selftext']) ? $post['selftext'] : $post['url'];
            $subject = $post['title'] or $body;

            $result[] = new SerializationMessage([
                "from" => $from . $codePostfix,
                "subject" => $subject,
                "parent" => $parent,
                "created" => $created,
                "id" => $id . $codePostfix,
                "body" => $body,
                "thread" =>  $threadId . $codePostfix,
                "uri" => $url
            ]);
        }

        return $result;
    }

    public function canHandleQuery(string $query, array $opts = []): bool
    {

        if (preg_match('/'. $this->getCode() .'/', $query)) {
            return true;
        }

        if ($opts["catalog_default_driver"] === $this->getCode()) {
            return true;
        }

        return false;
    }

    protected function normalizeQuery(string $query): string {

        if (! preg_match('/\/$/', $query)) {
            $query = $query . '/';
        }

        if (! preg_match('/^http:\/$/', $query)) {
            $query = 'https://' . $this->getCode() . '/r/' . $query;
        }

        return preg_replace('/\/$/', '.json', $query);
    }
}
