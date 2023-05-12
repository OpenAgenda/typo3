ajaxPage = PAGE
ajaxPage {
    typeNum = 46304

    config {
        disableAllHeaderCode = 1
        additionalHeaders = Content-type:application/json
        xhtml_cleaning = 0
        debug = 0
        no_cache = 1
        admPanel = 0
    }

    10 = USER_INT
    10 {
        userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
        extensionName= Openagenda
        pluginName = Ajax
        vendorName = Openagenda
        action = ajaxCallback
    }
}


filtersPage = PAGE
filtersPage {
    typeNum = 46305

    config {
        disableAllHeaderCode = 1
        additionalHeaders = Content-type:application/json
        xhtml_cleaning = 0
        debug = 0
        no_cache = 1
        admPanel = 0
    }

    10 = USER_INT
    10 {
        userFunc = TYPO3\CMS\Extbase\Core\Bootstrap->run
        extensionName= Openagenda
        pluginName = Filters
        vendorName = Openagenda
        action = filtersCallback
    }
}