<?php
declare(strict_types=1);

namespace Themes\AbstractBlogTheme\Controllers;

interface ConfigurableController
{
    /**
     * @return string
     */
    public function getTemplate();

    /**
     * @return int
     */
    public function getItemsPerPage();

    /**
     * @return int
     */
    public function getResponseTtl();
}
