<?php

declare(strict_types=1);

namespace Shyim\Mjml\Renderer;

use Shyim\Mjml\Context\GlobalContext;
use Shyim\Mjml\Helper\Fonts;
use Shyim\Mjml\MjmlOptions;

final class Skeleton
{
    /**
     * Build the complete HTML document wrapping the rendered body content.
     */
    public function build(string $bodyContent, GlobalContext $context, MjmlOptions $options): string
    {
        $fontsTags = Fonts::buildFontsTags($bodyContent, $context->inlineStyles, $context->fonts);
        $mediaQueriesTags = $this->buildMediaQueriesTags($context);
        $componentStyleTags = $this->buildStyleFromComponents($context);
        $styleTags = $this->buildStyleFromTags($context);
        $headRaw = implode("\n", array_filter($context->headRaw));
        $previewTag = $this->buildPreview($context->preview);

        $backgroundStyle = $context->backgroundColor !== ''
            ? "background-color:{$context->backgroundColor};"
            : '';

        $beforeDoctype = $context->beforeDoctype !== ''
            ? $context->beforeDoctype . "\n"
            : '';

        return <<<HTML
{$beforeDoctype}<!doctype html>
<html lang="{$context->language}" dir="{$context->dir}" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
  <head>
    <title>{$context->title}</title>
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style type="text/css">
      #outlook a {
        padding: 0;
      }

      body {
        margin: 0;
        padding: 0;
        -webkit-text-size-adjust: 100%;
        -ms-text-size-adjust: 100%;
      }

      table,
      td {
        border-collapse: collapse;
        mso-table-lspace: 0pt;
        mso-table-rspace: 0pt;
      }

      img {
        border: 0;
        height: auto;
        line-height: 100%;
        outline: none;
        text-decoration: none;
        -ms-interpolation-mode: bicubic;
      }

      p {
        display: block;
        margin: 13px 0;
      }

    </style>
    <!--[if mso]>
    <noscript>
    <xml>
    <o:OfficeDocumentSettings>
      <o:AllowPNG/>
      <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    </noscript>
    <![endif]-->
    <!--[if lte mso 11]>
    <style type="text/css">
      .mj-outlook-group-fix { width:100% !important; }
    </style>
    <![endif]-->
    {$fontsTags}
    {$mediaQueriesTags}
    {$componentStyleTags}
    {$styleTags}
    {$headRaw}
  </head>
  <body style="word-spacing:normal;{$backgroundStyle}">
    {$previewTag}
    {$bodyContent}
  </body>
</html>
HTML;
    }

    private function buildMediaQueriesTags(GlobalContext $context): string
    {
        if ($context->mediaQueries === []) {
            return '';
        }

        $baseRules = [];
        $thunderbirdRules = [];
        $owaRules = [];

        foreach ($context->mediaQueries as $className => $rule) {
            $baseRules[] = ".{$className} {$rule}";
            $thunderbirdRules[] = ".moz-text-html .{$className} {$rule}";
            $owaRules[] = "[owa] .{$className} {$rule}";
        }

        $breakpoint = $context->breakpoint;
        $baseRulesStr = implode("\n", $baseRules);
        $thunderbirdRulesStr = implode("\n", $thunderbirdRules);

        $output = <<<HTML

    <style type="text/css">
      @media only screen and (min-width:{$breakpoint}) {
        {$baseRulesStr}
      }
    </style>
    <style media="screen and (min-width:{$breakpoint})">
      {$thunderbirdRulesStr}
    </style>


HTML;

        if ($context->forceOWADesktop) {
            $owaRulesStr = implode("\n", $owaRules);

            $output .= <<<HTML
<style type="text/css">
{$owaRulesStr}
</style>
HTML;
        }

        return $output;
    }

    private function buildStyleFromComponents(GlobalContext $context): string
    {
        if ($context->componentsHeadStyle === [] && $context->headStyle === []) {
            return '';
        }

        $styles = '';

        foreach ($context->componentsHeadStyle as $styleFunc) {
            $result = $styleFunc($context->breakpoint);
            if ($result !== '') {
                $styles .= $result . "\n";
            }
        }

        foreach ($context->headStyle as $styleFunc) {
            $result = $styleFunc($context->breakpoint);
            if ($result !== '') {
                $styles .= $result . "\n";
            }
        }

        if ($styles === '') {
            return '';
        }

        return "    <style type=\"text/css\">\n{$styles}    </style>";
    }

    private function buildStyleFromTags(GlobalContext $context): string
    {
        if ($context->styles === []) {
            return '';
        }

        $styles = implode("\n", $context->styles);

        return "    <style type=\"text/css\">\n{$styles}\n    </style>";
    }

    private function buildPreview(string $preview): string
    {
        if ($preview === '') {
            return '';
        }

        return '<div style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;">'
            . htmlspecialchars($preview, \ENT_QUOTES, 'UTF-8')
            . '</div>';
    }
}
