<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;

class JsonLdPlace extends JsonLdObject
{
    /**
     * @var string
     * @JMS\SerializedName("@type")
     */
    public static $type = "Place";

    /**
     * @var string
     */
    public $name;

    /**
     * AmpPerson constructor.
     *
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }
}
