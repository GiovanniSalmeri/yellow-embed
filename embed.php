<?php
// Embed extension, https://github.com/GiovanniSalmeri/yellow-embed

class YellowEmbed {
    const VERSION = "0.9.1";
    public $yellow;         // access to API
    public $embedUrls;      // templates for embed URLs

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("embedStyle", "flexible");
    }

    // Handle startup
    public function onStartup() {
        $fileName = $this->yellow->system->get("coreExtensionDirectory")."embed.ini";
        $fileData = $this->yellow->toolbox->readFile($fileName);
        $this->embedUrls = $this->yellow->toolbox->getTextSettings($fileData, "");
    }

    // Handle page content element
    public function onParseContentElement($page, $name, $text, $attributes, $type) {
        $output = null;
        if ($name=="embed" && ($type=="block" || $type=="inline")) {
            list($type, $id, $style, $width, $height) = $this->yellow->toolbox->getTextArguments($text);
            if (isset($this->embedUrls[$type])) {
                list($sourceTemplate, $fixedHeight) = preg_split("/\s*,\s*/", $this->embedUrls[$type].",");
                if (is_string_empty($style)) $style = $this->yellow->system->get("embedStyle");
                if (is_string_empty($height)) $height = $fixedHeight ?: $width;
                $width = is_string_empty($width) && $fixedHeight!=="" ? "100%" : $this->convertValueAndUnit($width, 640);
                $height = $this->convertValueAndUnit($height, 360);
                $output .= "<div".(is_string_empty($fixedHeight) ? " class=\"".htmlspecialchars($style)."\"" : "").">";
                $sourceTemplate = str_replace("@lang", $page->get("language"), $sourceTemplate);
                $parts = explode("/", $id);
                $sourceTemplate = preg_replace_callback("/@(\d)/", function($matches) use ($parts) { return $parts[$matches[1]-1] ?? ""; }, $sourceTemplate);
                $dimensions = $width && $height ? " width=\"".htmlspecialchars($width)."\" height=\"".htmlspecialchars($height)."\"" : "";
                $output .= "<iframe class=\"embed embed-$type\" src=\"{$sourceTemplate}\" frameborder=\"0\" allow=\"accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen\" loading=\"lazy\" sandbox=\"allow-scripts allow-same-origin\"{$dimensions}><p>{$sourceTemplate}</p></iframe>";
                $output .= "</div>\n";
            }
        }
        return $output;
    }

    // Return value according to unit
    public function convertValueAndUnit($text, $valueBase) {
        $value = $unit = "";
        if (preg_match("/([\d\.]+)(\S*)/", $text, $matches)) {
            $value = $matches[1];
            $unit = $matches[2];
            if ($unit=="%") $value = $valueBase * $value / 100;
        }
        return intval($value);
    }
}
