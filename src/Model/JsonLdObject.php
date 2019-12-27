<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;

class JsonLdObject
{
    /**
     * @JMS\SerializedName("@context")
     * @JMS\Groups({"collection"})
     * @var string
     */
    public static $context = "http://schema.org";

    /**
     * @var string
     * @JMS\SerializedName("@type")
     * @JMS\Groups({"collection"})
     */
    public static $type = "NewsArticle";
}
