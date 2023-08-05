## Introdution

This is an ItIsAllMail driver for site reddit.com.

Supported features:

- fetcher
- catalog

## Installation:

1) Install ItIsAllMail.
2) Install the driver.

    cd lib/ItIsAllMail/Driver/
    git clone https://github.com/yalexwander/iam-driver-reddit.com reddit.com

3) Add driver to ItIsAllMail `conf/config.yml`:

```
drivers :
  - "reddit.com"
```

2) Add source in `conf/sources.yml`:

```
- url: https://www.reddit.com/r/symfony/
  mailbox_base_dir: /tmp
  mailbox: mailbox_reddit
```

or

```
- url: https://www.reddit.com/r/symfony/comments/15ek3ll/symfony_633_released/
  mailbox_base_dir: /tmp
  mailbox: mailbox_reddit
```

Two types of sources supported: subreddit feed and thread. Trailing slash is required anyway.


# Using catalog

Here is example of using catalog command:

`!cat crows` fetches `https://www.reddit.com/r/crows/` subreddit to your catalog dir.
