<?php

namespace ItIsAllMail\Driver;

use ItIsAllMail\Interfaces\FetchDriverInterface;
use ItIsAllMail\DriverCommon\AbstractFetcherDriver;
use ItIsAllMail\Factory\CatalogDriverFactory;
use ItIsAllMail\HtmlToText;
use ItIsAllMail\CoreTypes\SerializationMessage;
use ItIsAllMail\Utils\Browser;
use ItIsAllMail\Utils\Debug;
use ItIsAllMail\Utils\URLProcessor;
use voku\helper\HtmlDomParser;
use voku\helper\SimpleHtmlDom;
use voku\helper\SimpleHtmlDomInterface;
use ItIsAllMail\CoreTypes\Source;

class RedditFetcherDriver extends AbstractFetcherDriver implements FetchDriverInterface
{
    protected string $driverCode = "reddit.com";

    protected array $posts = [];
    protected string $threadId;
    protected string $threadUrl;
    protected string $codePostfix;

    public function __construct(array $appConfig, array $opts)
    {
        parent::__construct($appConfig, $opts);
        $this->codePostfix = '@' . $this->getCode();
    }

    public function getPosts(Source $source): array
    {
        $normalizedURL = $this->normalizeURL($source["url"]);

        if ($this->isCatalogQuery($source)) {
            $catalog = (new CatalogDriverFactory($this->appConfig))
                ->getCatalogDriver($source["url"], ['source' => $source]);

            $this->posts = $catalog->queryCatalog($source["url"]);
        } else {
            $json = Browser::getAsString($normalizedURL);
            $prePosts = json_decode($json, true);

            $mainPost = $prePosts[0]['data']['children'][0]['data'];
            $this->processPost($mainPost);
            array_shift($prePosts);

            foreach ($prePosts as $pp) {
                $this->processPost($pp['data']);
            }
        }

        return $this->posts;
    }

    protected function processPost(array $post, ?string $parentId = null): void
    {
        if (empty($this->threadId)) {
            $this->threadId = $post['id'];
            $this->threadUrl = $post['url'];
        }

        $id = $post['id'] ?? null;
        if ($id) {
            $body = $post['body'] ?? $post['selftext'];
            $subject = $post['title'] ?? $body;
            $from = $post['author'];
            $created = (new \DateTime())->setTimestamp($post['created_utc']);
            $url = $post['url'] ?? $this->threadUrl;
            $parent = $parentId ?? $this->threadId;

            $this->posts[] = new SerializationMessage([
                "from" => $from . $this->codePostfix,
                "subject" => $subject,
                "parent" => $parent . $this->codePostfix,
                "created" => $created,
                "id" => $id . $this->codePostfix,
                "body" => $body,
                "thread" =>  $this->threadId . $this->codePostfix,
                "uri" => $url
            ]);
        }

        if (!empty($post['children'])) {
            foreach ($post['children'] as $childPost) {
                $this->processPost($childPost['data'], $id);
            }
        }

        if (!empty($post['replies'])) {
            foreach ($post['replies']['data']['children'] as $childPost) {
                $this->processPost($childPost['data'], $id);
            }
        }
    }

    protected function normalizeURL(string $url): string
    {
        return preg_replace('/\/$/', '.json', $url);
    }

    protected function isCatalogQuery(Source $source): bool
    {
        if (preg_match('/\/comments\//', $source["url"])) {
            return false;
        } else {
            return true;
        }
    }
}
