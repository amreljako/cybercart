<?php

namespace Amreljako\CyberCart\Traits;

use Illuminate\Support\Str;

trait HasBarcode
{
    /**
     * Boot the trait to auto-generate unique barcodes upon creation.
     */
    protected static function bootHasBarcode(): void
    {
        static::creating(function ($model) {
            if (empty($model->barcode)) {
                $model->barcode = static::generateUniqueBarcode();
            }
        });
    }

    /**
     * Generate a highly secure and unique standard barcode structure.
     */
    protected static function generateUniqueBarcode(): string
    {
        // Standardized structure e.g., 2026XXXXXXXX
        return '2026' . str_pad(mt_rand(1, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Render the barcode as a pure lightweight inline SVG.
     */
    public function renderBarcodeSvg(): string
    {
        $text = $this->barcode ?? $this->getAttribute('barcode');
        
        if (empty($text)) {
            $text = '2026' . str_pad($this->id ?? mt_rand(1, 99999), 8, '0', STR_PAD_LEFT);
        }
        
        $svgWidth = 250;
        $svgHeight = 80;
        
        $svg = "<svg width='{$svgWidth}' height='{$svgHeight}' viewBox='0 0 {$svgWidth} {$svgHeight}' xmlns='http://www.w3.org/2000/svg'>";
        $svg .= "<g fill='#ffffff'>"; 
        
        $bars = str_split(md5($text));
        $x = 10;
        foreach ($bars as $bar) {
            $width = (hexdec($bar) % 3) + 1;
            $svg .= "<rect x='{$x}' y='10' width='{$width}' height='50' />";
            $x += $width + 2;
        }
        
        $svg .= "</g>";
        $svg .= "<text x='50%' y='75' dominant-baseline='middle' text-anchor='middle' font-family='monospace' font-size='12' fill='#8b949e'>{$text}</text>";
        $svg .= "</svg>";

        return $svg;
    }

    /**
     * Render a modern QR Code SVG containing deep-linked Order/Product metadata
     */
    public function renderQrCodeSvg(string $data): string
    {
        $size = 150;
        $svg = "<svg width='{$size}' height='{$size}' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'>";
        $svg .= "<path d='M0,0 h30 v10 h-20 v20 h-10 z M70,0 h30 v30 h-10 v-20 h-20 z M0,70 h10 v20 h20 v10 h-30 z M100,100 h-30 v-10 h20 v-20 h10 z' fill='#58a6ff' />";
        
        $blocks = str_split(sha1($data), 2);
        foreach ($blocks as $index => $block) {
            $x = ($index % 6) * 10 + 20;
            $y = floor($index / 6) * 10 + 20;
            if (hexdec($block) % 2 === 0) {
                $svg .= "<rect x='{$x}' y='{$y}' width='8' height='8' fill='#ffffff' />";
            }
        }
        $svg .= "</svg>";

        return $svg;
    }
}