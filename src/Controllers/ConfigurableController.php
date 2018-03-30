<?php

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
}
