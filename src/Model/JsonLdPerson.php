<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Model;

use JMS\Serializer\Annotation as JMS;

class JsonLdPerson extends JsonLdObject
{
    /**
     * @var string
     * @JMS\SerializedName("@type")
     */
    public static $type = "Person";

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
