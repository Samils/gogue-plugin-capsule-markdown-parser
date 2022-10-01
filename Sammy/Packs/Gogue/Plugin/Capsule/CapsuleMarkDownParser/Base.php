<?php
/**
 * @version 2.0
 * @author Sammy
 *
 * @keywords Samils, ils, php framework
 * -----------------
 * @package Sammy\Packs\Gogue\Plugin\Capsule\CapsuleMarkDownParser
 * - Autoload, application dependencies
 *
 * MIT License
 *
 * Copyright (c) 2020 Ysare
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
namespace Sammy\Packs\Gogue\Plugin\Capsule\CapsuleMarkDownParser {
  use Sammy\Packs\Gogue\Transpiler\Capsule\Base as Capsule;
  use Sammy\Packs\Gogue\Component\Code\BlockEncoder;
  use Sammy\Packs\Gogue\Code\HtmlCommentsReader;
  /**
   * Make sure the module base internal trait is not
   * declared in the php global scope before creating
   * it.
   * It ensures that the script flux is not interrupted
   * when trying to run the current command by the cli
   * API.
   */
  if (!trait_exists ('Sammy\Packs\Gogue\Plugin\Capsule\CapsuleMarkDownParser\Base')) {
  /**
   * @trait Base
   * Base internal trait for the
   * Gogue\Plugin\Capsule\CapsuleMarkDownParser module.
   * -
   * This is (in the ils environment)
   * an instance of the php module,
   * which should contain the module
   * core functionalities that should
   * be extended.
   * -
   * For extending the module, just create
   * an 'exts' directory in the module directory
   * and boot it by using the ils directory boot.
   * -
   */
  trait Base {

    /**
     * @var array
     */
    private $currentTag = null;

    /**
     * @var string line tag regEx
     */
    private $lineHtmlTagRe = '/^(<(\/)?([^>]+)>)$/i';

    /**
     * @method string
     */
    public function run (array $options = []) {

      $capsuleName = $this->generateCapsuleName ();

      $capsuleChildren = $this->readMarkDown ($this->code);

      /**
       * Default file code
       */
      $code = join ("\n", [
        "@def {$capsuleName}",
        "\t<Fragment>{$capsuleChildren}</Fragment>",
        "@end",
        "@export default {$capsuleName}\n\n"
      ]);

      $capsule = new Capsule ($code);

      #$blockEncoder = new BlockEncoder;

      #$encoded = $blockEncoder->encodeBlocks ($this->code);

      #$this->code = $encoded [0];
      #$this->store = $encoded;

      return ($capsule->run ());
    }

    /**
     * @method string readMarkDown
     */
    public function readMarkDown (string $sourceCode) {
      $htmlCommentsReader = new HtmlCommentsReader;

      $sourceCode = $htmlCommentsReader ($sourceCode);

      $headingRe = '/\s*(\#+)([^\n]+)/';
      $htmlTagRe = '/(<(\/)?([^>]+)>)/i';


      $sourceCode = preg_replace_callback ($htmlTagRe, function ($match) {
        return join ("", ["\n", $match [0], "\n"]);
      }, $sourceCode);

      # TODO:
      #
      # encode blocks
      #

      $sourceCodeLines = preg_split ('/\n+/', $sourceCode);

      foreach ($sourceCodeLines as $i => $line) {
        if (empty (trim ($line))) {
          continue;
        }

        if ($this->insideHtmlElement ($line)) {
          if (!preg_match ($this->lineHtmlTagRe, trim ($line))) {
            $sourceCodeLines [$i] = join ('', ["\"", $line, "\""]);
          }
        } elseif (preg_match ($headingRe, trim ($line), $match)) {
          $headingText = trim ($match [2]);
          $headingSize = strlen (trim ($match [1]));

          $sourceCodeLines [$i] = join ('', ["<h{$headingSize}>", $headingText, "</h{$headingSize}>"]);
        } else {
          $sourceCodeLines [$i] = join ('', ['<p>"', $line, '"</p>']);
        }
      }

      $sourceCode = join ("\n", $sourceCodeLines);

      return  ($sourceCode);
    }

    private function insideHtmlElement (string $content) {
      $content = trim ($content);
      $lineHtmlTagRe = $this->lineHtmlTagRe;

      if (preg_match ($lineHtmlTagRe, $content, $match)) {
        $tagName = trim (strtolower ($match [3]));
        $closingTag = (boolean)(!empty ($match [2]));

        if (!$closingTag) {
          if (!$this->currentTag) {
            $this->currentTag = [
              'name' => $tagName,
              'eEnds' => 1
            ];

            return true;
          } elseif ($this->currentTag ['name'] === $tagName) {
            $this->currentTag ['eEnds'] += 1;

            return true;
          } elseif ($this->currentTag) {
            return true;
          }
        } elseif ($this->currentTag) {
          # consling tag
          if ($tagName === $this->currentTag ['name']) {
            if ($this->currentTag ['eEnds'] === 1) {
              $this->currentTag = null;
            } else {
              $this->currentTag ['eEnds'] -= 1;
            }
          }

          return true;
        }
      } elseif ($this->currentTag) {
        return true;
      }
    }
  }}
}
