<?php
namespace CodeIgniter\Messenger\Handlers;

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2018, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author     EllisLab Dev Team
 * @copyright  Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright  Copyright (c) 2014 - 2018, British Columbia Institute of Technology (http://bcit.ca/)
 * @license    http://opensource.org/licenses/MIT    MIT License
 * @link       https://codeigniter.com
 * @since      Version 1.0.0
 * @filesource
 */

use CodeIgniter\Messenger\Message;
use CodeIgniter\Messenger\Exceptions\MessengerException;
use Config\Mimes;
use Psr\Log\LoggerAwareTrait;

/**
 * CodeIgniter Messenger Class
 *
 * Permits email to be sent using Mail, Sendmail, or SMTP.
 *
 * @package    CodeIgniter
 * @subpackage Libraries
 * @category   Libraries
 * @author     EllisLab Dev Team
 * @link       https://codeigniter.com/user_guide/libraries/email.html
 */
class MailHandler extends BaseHandler
{

	/**
	 * Message format.
	 *
	 * @var string 'text' or 'html'
	 */
	public $mailType = 'text';

	/**
	 * Character set (default: utf-8)
	 *
	 * @var string
	 */
	public $charset = 'utf-8';

	/**
	 * Alternative message (for HTML messages only)
	 *
	 * @var string
	 */
	public $altMessage = '';

	/**
	 * Whether to validate e-mail addresses.
	 *
	 * @var boolean
	 */
	public $validate = true;

	/**
	 * X-Priority header value.
	 *
	 * @var integer 1-5
	 */
	public $priority = 3;            // Default priority (1 - 5)

	/**
	 * Newline character sequence.
	 * Use "\r\n" to comply with RFC 822.
	 *
	 * @link http://www.ietf.org/rfc/rfc822.txt
	 * @var  string "\r\n" or "\n"
	 */
	public $newline = "\n";            // Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)

	/**
	 * CRLF character sequence
	 *
	 * RFC 2045 specifies that for 'quoted-printable' encoding,
	 * "\r\n" must be used. However, it appears that some servers
	 * (even on the receiving end) don't handle it properly and
	 * switching to "\n", while improper, is the only solution
	 * that seems to work for all environments.
	 *
	 * @link http://www.ietf.org/rfc/rfc822.txt
	 * @var  string
	 */
	public $CRLF = "\n";

	/**
	 * Whether to use Delivery Status Notification.
	 *
	 * @var boolean
	 */
	public $DSN = false;

	/**
	 * Whether to send multipart alternatives.
	 * Yahoo! doesn't seem to like these.
	 *
	 * @var boolean
	 */
	public $sendMultipart = true;

	/**
	 * Whether to send messages to BCC recipients in batches.
	 *
	 * @var boolean
	 */
	public $BCCBatchMode = false;

	/**
	 * BCC Batch max number size.
	 *
	 * @see self::$BCCBatchMode
	 * @var integer
	 */
	public $BCCBatchSize = 200;

	//--------------------------------------------------------------------

	/**
	 * Subject header
	 *
	 * @var string
	 */
	protected $subject = '';

	/**
	 * Message body
	 *
	 * @var string
	 */
	protected $body = '';

	/**
	 * Final message body to be sent.
	 *
	 * @var string
	 */
	protected $finalBody = '';

	/**
	 * Final headers to send
	 *
	 * @var string
	 */
	protected $headerStr = '';

	/**
	 * SMTP Connection socket placeholder
	 *
	 * @var resource
	 */
	protected $SMTPConnect = '';

	/**
	 * Mail encoding
	 *
	 * @var string '8bit' or '7bit'
	 */
	protected $encoding = '8bit';

	/**
	 * Whether to send a Reply-To header
	 *
	 * @var boolean
	 */
	protected $replyToFlag = false;

	/**
	 * Debug messages
	 *
	 * @see self::printDebugger()
	 * @var array
	 */
	protected $debugMessage = [];

	/**
	 * Recipients
	 *
	 * @var array
	 */
	protected $recipients = [];

	/**
	 * CC Recipients
	 *
	 * @var array
	 */
	protected $CCArray = [];

	/**
	 * BCC Recipients
	 *
	 * @var array
	 */
	protected $BCCArray = [];

	/**
	 * Message headers
	 *
	 * @var array
	 */
	protected $headers = [];

	/**
	 * Attachment data
	 *
	 * @var array
	 */
	protected $attachments = [];

	/**
	 * Valid $protocol values
	 *
	 * @see self::$protocol
	 * @var array
	 */
	protected $protocols = [
		'mail',
		'sendmail',
		'smtp',
	];

	/**
	 * Base charsets
	 *
	 * Character sets valid for 7-bit encoding,
	 * excluding language suffix.
	 *
	 * @var array
	 */
	protected $baseCharsets = [
		'us-ascii',
		'iso-2022-',
	];

	/**
	 * Bit depths
	 *
	 * Valid mail encodings
	 *
	 * @see self::$encoding
	 * @var array
	 */
	protected $bitDepths = [
		'7bit',
		'8bit',
	];

	/**
	 * $priority translations
	 *
	 * Actual values to send with the X-Priority header
	 *
	 * @var array
	 */
	protected $priorities = [
		1 => '1 (Highest)',
		2 => '2 (High)',
		3 => '3 (Normal)',
		4 => '4 (Low)',
		5 => '5 (Lowest)',
	];

	/**
	 * mbstring.func_overload flag
	 *
	 * @var boolean
	 */
	protected static $func_overload;

	/**
	 * Logger instance to record error messages and awarnings.
	 *
	 * @var \PSR\Log\LoggerInterface
	 */
	protected $logger;

	//--------------------------------------------------------------------

	/**
	 * Constructor - Sets Preferences
	 *
	 * The constructor can be passed an array of config values
	 *
	 * @param array|null $config
	 */
	public function __construct($config = null)
	{
		$this->protocol = 'mail';
		$this->initialize($config);

		log_message('info', 'Messenger Class Initialized');
	}

	//--------------------------------------------------------------------

	/**
	 * Initialize preferences
	 *
	 * @param array|\Config\Messenger $config
	 *
	 * @return $this
	 */
	public function initialize($config)
	{
		$this->clear();

		if ($config instanceof \Config\Messenger)
		{
			$config = get_object_vars($config);
		}

		foreach (get_class_vars(get_class($this)) as $key => $value)
		{
			if (property_exists($this, $key) && isset($config[$key]))
			{
				$method = 'set' . ucfirst($key);

				if (method_exists($this, $method))
				{
					$this->$method($config[$key]);
				}
			}
		}

		$this->charset  = strtoupper($this->charset);
		$this->SMTPAuth = isset($this->SMTPUser[0], $this->SMTPPass[0]);

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Initialize the Data
	 *
	 * @param boolean $clearAttachments
	 *
	 * @return $this
	 */
	public function clear($clearAttachments = false)
	{
		$this->subject      = '';
		$this->body         = '';
		$this->finalBody    = '';
		$this->headerStr    = '';
		$this->replyToFlag  = false;
		$this->recipients   = [];
		$this->CCArray      = [];
		$this->BCCArray     = [];
		$this->headers      = [];
		$this->debugMessage = [];

		//      $this->setHeader('Date', $this->setDate());

		if ($clearAttachments !== false)
		{
			$this->attachments = [];
		}

		return $this;
	}

	//--------------------------------------------------------------------

	/**
	 * Build final headers
	 */
	protected function buildHeaders()
	{
		$this->setHeader('User-Agent', $this->userAgent);
		$this->setHeader('X-Sender', $this->cleanEmail($this->headers['From']));
		$this->setHeader('X-Mailer', $this->userAgent);
		$this->setHeader('X-Priority', $this->priorities[$this->priority]);
		$this->setHeader('Message-ID', $this->getMessageID());
		$this->setHeader('Mime-Version', '1.0');
	}

	//--------------------------------------------------------------------

	/**
	 * Write Headers as a string
	 */
	protected function writeHeaders()
	{
		if ($this->protocol === 'mail')
		{
			if (isset($this->headers['Subject']))
			{
				$this->subject = $this->headers['Subject'];
				unset($this->headers['Subject']);
			}
		}

		reset($this->headers);
		$this->headerStr = '';

		foreach ($this->headers as $key => $val)
		{
			$val = trim($val);

			if ($val !== '')
			{
				$this->headerStr .= $key . ': ' . $val . $this->newline;
			}
		}

		if ($this->getProtocol() === 'mail')
		{
			$this->headerStr = rtrim($this->headerStr);
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Build Final Body and attachments
	 */
	protected function buildMessage()
	{
		if ($this->wordWrap === true && $this->mailType !== 'html')
		{
			$this->body = $this->wordWrap($this->body);
		}

		$this->writeHeaders();

		$hdr  = ($this->getProtocol() === 'mail') ? $this->newline : '';
		$body = '';

		switch ($this->getContentType())
		{
			case 'plain':

				$hdr .= 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
						. 'Content-Transfer-Encoding: ' . $this->getEncoding();

				if ($this->getProtocol() === 'mail')
				{
					$this->headerStr .= $hdr;
					$this->finalBody  = $this->body;
				}
				else
				{
					$this->finalBody = $hdr . $this->newline . $this->newline . $this->body;
				}

				return;

			case 'html':

				if ($this->sendMultipart === false)
				{
					$hdr .= 'Content-Type: text/html; charset=' . $this->charset . $this->newline
							. 'Content-Transfer-Encoding: quoted-printable';
				}
				else
				{
					$boundary = uniqid('B_ALT_', true);
					$hdr     .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

					$body .= $this->getMimeMessage() . $this->newline . $this->newline
							. '--' . $boundary . $this->newline
							. 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
							. 'Content-Transfer-Encoding: ' . $this->getEncoding() . $this->newline . $this->newline
							. $this->getAltMessage() . $this->newline . $this->newline
							. '--' . $boundary . $this->newline
							. 'Content-Type: text/html; charset=' . $this->charset . $this->newline
							. 'Content-Transfer-Encoding: quoted-printable' . $this->newline . $this->newline;
				}

				$this->finalBody = $body . $this->prepQuotedPrintable($this->body) . $this->newline . $this->newline;

				if ($this->getProtocol() === 'mail')
				{
					$this->headerStr .= $hdr;
				}
				else
				{
					$this->finalBody = $hdr . $this->newline . $this->newline . $this->finalBody;
				}

				if ($this->sendMultipart !== false)
				{
					$this->finalBody .= '--' . $boundary . '--';
				}

				return;

			case 'plain-attach':

				$boundary = uniqid('B_ATC_', true);
				$hdr     .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

				if ($this->getProtocol() === 'mail')
				{
					$this->headerStr .= $hdr;
				}

				$body .= $this->getMimeMessage() . $this->newline
						. $this->newline
						. '--' . $boundary . $this->newline
						. 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
						. 'Content-Transfer-Encoding: ' . $this->getEncoding() . $this->newline
						. $this->newline
						. $this->body . $this->newline . $this->newline;

				$this->appendAttachments($body, $boundary);

				break;
			case 'html-attach':

				$alt_boundary  = uniqid('B_ALT_', true);
				$last_boundary = null;

				if ($this->attachmentsHaveMultipart('mixed'))
				{
					$atc_boundary  = uniqid('B_ATC_', true);
					$hdr          .= 'Content-Type: multipart/mixed; boundary="' . $atc_boundary . '"';
					$last_boundary = $atc_boundary;
				}

				if ($this->attachmentsHaveMultipart('related'))
				{
					$rel_boundary        = uniqid('B_REL_', true);
					$rel_boundary_header = 'Content-Type: multipart/related; boundary="' . $rel_boundary . '"';

					if (isset($last_boundary))
					{
						$body .= '--' . $last_boundary . $this->newline . $rel_boundary_header;
					}
					else
					{
						$hdr .= $rel_boundary_header;
					}

					$last_boundary = $rel_boundary;
				}

				if ($this->getProtocol() === 'mail')
				{
					$this->headerStr .= $hdr;
				}

				static::strlen($body) && $body .= $this->newline . $this->newline;
				$body                          .= $this->getMimeMessage() . $this->newline . $this->newline
						. '--' . $last_boundary . $this->newline
						. 'Content-Type: multipart/alternative; boundary="' . $alt_boundary . '"' . $this->newline . $this->newline
						. '--' . $alt_boundary . $this->newline
						. 'Content-Type: text/plain; charset=' . $this->charset . $this->newline
						. 'Content-Transfer-Encoding: ' . $this->getEncoding() . $this->newline . $this->newline
						. $this->getAltMessage() . $this->newline . $this->newline
						. '--' . $alt_boundary . $this->newline
						. 'Content-Type: text/html; charset=' . $this->charset . $this->newline
						. 'Content-Transfer-Encoding: quoted-printable' . $this->newline . $this->newline
						. $this->prepQuotedPrintable($this->body) . $this->newline . $this->newline
						. '--' . $alt_boundary . '--' . $this->newline . $this->newline;

				if (! empty($rel_boundary))
				{
					$body .= $this->newline . $this->newline;
					$this->appendAttachments($body, $rel_boundary, 'related');
				}

				// multipart/mixed attachments
				if (! empty($atc_boundary))
				{
					$body .= $this->newline . $this->newline;
					$this->appendAttachments($body, $atc_boundary, 'mixed');
				}

				break;
		}

		$this->finalBody = ($this->getProtocol() === 'mail') ? $body : $hdr . $this->newline . $this->newline . $body;
	}

	//--------------------------------------------------------------------

	/**
	 * Prepares attachment string
	 *
	 * @param string      &$body     Message body to append to
	 * @param string      $boundary  Multipart boundary
	 * @param string|null $multipart When provided, only attachments of this type will be processed
	 *
	 * @return string
	 */
	protected function appendAttachments(&$body, $boundary, $multipart = null)
	{
		for ($i = 0, $c = count($this->attachments); $i < $c; $i ++)
		{
			if (isset($multipart) && $this->attachments[$i]['multipart'] !== $multipart)
			{
				continue;
			}

			$name = isset($this->attachments[$i]['name'][1]) ? $this->attachments[$i]['name'][1] : basename($this->attachments[$i]['name'][0]);

			$body .= '--' . $boundary . $this->newline
					. 'Content-Type: ' . $this->attachments[$i]['type'] . '; name="' . $name . '"' . $this->newline
					. 'Content-Disposition: ' . $this->attachments[$i]['disposition'] . ';' . $this->newline
					. 'Content-Transfer-Encoding: base64' . $this->newline
					. (empty($this->attachments[$i]['cid']) ? '' : 'Content-ID: <' . $this->attachments[$i]['cid'] . '>' . $this->newline)
					. $this->newline
					. $this->attachments[$i]['content'] . $this->newline;
		}

		// $name won't be set if no attachments were appended,
		// and therefore a boundary wouldn't be necessary
		empty($name) || $body .= '--' . $boundary . '--';
	}

	//--------------------------------------------------------------------

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected function prepQuotedPrintable($str)
	{
		// ASCII code numbers for "safe" characters that can always be
		// used literally, without encoding, as described in RFC 2049.
		// http://www.ietf.org/rfc/rfc2049.txt
		static $ascii_safe_chars = [
			// ' (  )   +   ,   -   .   /   :   =   ?
			39,
			40,
			41,
			43,
			44,
			45,
			46,
			47,
			58,
			61,
			63,
			// numbers
			48,
			49,
			50,
			51,
			52,
			53,
			54,
			55,
			56,
			57,
			// upper-case letters
			65,
			66,
			67,
			68,
			69,
			70,
			71,
			72,
			73,
			74,
			75,
			76,
			77,
			78,
			79,
			80,
			81,
			82,
			83,
			84,
			85,
			86,
			87,
			88,
			89,
			90,
			// lower-case letters
			97,
			98,
			99,
			100,
			101,
			102,
			103,
			104,
			105,
			106,
			107,
			108,
			109,
			110,
			111,
			112,
			113,
			114,
			115,
			116,
			117,
			118,
			119,
			120,
			121,
			122,
		];

		// We are intentionally wrapping so mail servers will encode characters
		// properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(['{unwrap}', '{/unwrap}'], '', $str);

		// RFC 2045 specifies CRLF as "\r\n".
		// However, many developers choose to override that and violate
		// the RFC rules due to (apparently) a bug in MS Exchange,
		// which only works with "\n".
		if ($this->CRLF === "\r\n")
		{
			return quoted_printable_encode($str);
		}

		// Reduce multiple spaces & remove nulls
		$str = preg_replace(['| +|', '/\x00+/'], [' ', ''], $str);

		// Standardize newlines
		if (strpos($str, "\r") !== false)
		{
			$str = str_replace(["\r\n", "\r"], "\n", $str);
		}

		$escape = '=';
		$output = '';

		foreach (explode("\n", $str) as $line)
		{
			$length = static::strlen($line);
			$temp   = '';

			// Loop through each character in the line to add soft-wrap
			// characters at the end of a line " =\r\n" and add the newly
			// processed line(s) to the output (see comment on $crlf class property)
			for ($i = 0; $i < $length; $i ++)
			{
				// Grab the next character
				$char  = $line[$i];
				$ascii = ord($char);

				// Convert spaces and tabs but only if it's the end of the line
				if ($ascii === 32 || $ascii === 9)
				{
					if ($i === ($length - 1))
					{
						$char = $escape . sprintf('%02s', dechex($ascii));
					}
				}
				// DO NOT move this below the $ascii_safe_chars line!
				//
				// = (equals) signs are allowed by RFC2049, but must be encoded
				// as they are the encoding delimiter!
				elseif ($ascii === 61)
				{
					$char = $escape . strtoupper(sprintf('%02s', dechex($ascii)));  // =3D
				}
				elseif (! in_array($ascii, $ascii_safe_chars, true))
				{
					$char = $escape . strtoupper(sprintf('%02s', dechex($ascii)));
				}

				// If we're at the character limit, add the line to the output,
				// reset our temp variable, and keep on chuggin'
				if ((static::strlen($temp) + static::strlen($char)) >= 76)
				{
					$output .= $temp . $escape . $this->CRLF;
					$temp    = '';
				}

				// Add the character to our temporary line
				$temp .= $char;
			}

			// Add our completed line to the output
			$output .= $temp . $this->CRLF;
		}

		// get rid of extra CRLF tacked onto the end
		return static::substr($output, 0, static::strlen($this->CRLF) * -1);
	}

	//--------------------------------------------------------------------

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.
	 * It's related but not identical to quoted-printable, so it has its
	 * own method.
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	protected function prepQEncoding($str)
	{
		$str = str_replace(["\r", "\n"], '', $str);

		if ($this->charset === 'UTF-8')
		{
			// Note: We used to have mb_encode_mimeheader() as the first choice
			//       here, but it turned out to be buggy and unreliable. DO NOT
			//       re-add it! -- Narf
			if (extension_loaded('iconv'))
			{
				$output = @iconv_mime_encode(
								'', $str, [
									'scheme'           => 'Q',
									'line-length'      => 76,
									'input-charset'    => $this->charset,
									'output-charset'   => $this->charset,
									'line-break-chars' => $this->CRLF,
								]);

				// There are reports that iconv_mime_encode() might fail and return FALSE
				if ($output !== false)
				{
					// iconv_mime_encode() will always put a header field name.
					// We've passed it an empty one, but it still prepends our
					// encoded string with ': ', so we need to strip it.
					return static::substr($output, 2);
				}

				$chars = iconv_strlen($str, 'UTF-8');
			}
			elseif (extension_loaded('mbstring'))
			{
				$chars = mb_strlen($str, 'UTF-8');
			}
		}

		// We might already have this set for UTF-8
		isset($chars) || $chars = static::strlen($str);

		$output = '=?' . $this->charset . '?Q?';
		for ($i = 0, $length = static::strlen($output); $i < $chars; $i ++)
		{
			$chr = ($this->charset === 'UTF-8' && ICONV_ENABLED === true) ? '=' . implode('=', str_split(strtoupper(bin2hex(iconv_substr($str, $i, 1, $this->charset))), 2)) : '=' . strtoupper(bin2hex($str[$i]));

			// RFC 2045 sets a limit of 76 characters per line.
			// We'll append ?= to the end of each line though.
			if ($length + ($l = static::strlen($chr)) > 74)
			{
				$output .= '?=' . $this->CRLF // EOL
						. ' =?' . $this->charset . '?Q?' . $chr; // New line
				$length  = 6 + static::strlen($this->charset) + $l; // Reset the length for the new line
			}
			else
			{
				$output .= $chr;
				$length += $l;
			}
		}

		// End the header
		return $output . '?=';
	}

	//--------------------------------------------------------------------

	/**
	 * Send a Message
	 *
	 * @param boolean $autoClear
	 *
	 * @return boolean
	 */
	public function send(Message $message, bool $autoClear = true, bool $reallySend = true)
	{
		if (! isset($this->headers['From']) && ! empty($this->fromEmail))
		{
			$this->setFrom($this->fromEmail, $this->fromName);
		}

		if (! isset($this->headers['From']))
		{
				throw MessengerException::forNoFrom();
		}

		if ($this->replyToFlag === false)
		{
			$this->setReplyTo($this->headers['From']);
		}

		if (empty($this->recipients) && ! isset($this->headers['To']) && empty($this->BCCArray) && ! isset($this->headers['Bcc']) && ! isset($this->headers['Cc'])
		)
		{
				throw MessengerException::forNoRecipients();
		}

		$this->buildHeaders();

		if ($this->BCCBatchMode && count($this->BCCArray) > $this->BCCBatchSize)
		{
			$this->batchBCCSend();

			if ($autoClear)
			{
				$this->clear();
			}

			return true;
		}

		$this->buildMessage();
		$result = $this->spoolEmail();

		if ($result && $autoClear)
		{
			$this->clear();
		}

		return $result;
	}

	//--------------------------------------------------------------------

	/**
	 * Batch Bcc Send. Sends groups of BCCs in batches
	 */
	public function batchBCCSend()
	{
		$float = $this->BCCBatchSize - 1;
		$set   = '';
		$chunk = [];

		for ($i = 0, $c = count($this->BCCArray); $i < $c; $i ++)
		{
			if (isset($this->BCCArray[$i]))
			{
				$set .= ', ' . $this->BCCArray[$i];
			}

			if ($i === $float)
			{
				$chunk[] = static::substr($set, 1);
				$float  += $this->BCCBatchSize;
				$set     = '';
			}

			if ($i === $c - 1)
			{
				$chunk[] = static::substr($set, 1);
			}
		}

		for ($i = 0, $c = count($chunk); $i < $c; $i ++)
		{
			unset($this->headers['Bcc']);

			$bcc = $this->cleanEmail($this->stringToArray($chunk[$i]));

			if ($this->protocol !== 'smtp')
			{
				$this->setHeader('Bcc', implode(', ', $bcc));
			}
			else
			{
				$this->BCCArray = $bcc;
			}

			$this->buildMessage();
			$this->spoolEmail();
		}
	}

	//--------------------------------------------------------------------

	/**
	 * Unwrap special elements
	 */
	protected function unwrapSpecials()
	{
		$this->finalBody = preg_replace_callback(
				'/\{unwrap\}(.*?)\{\/unwrap\}/si', [
					$this,
					'removeNLCallback',
				], $this->finalBody
		);
	}

	//--------------------------------------------------------------------

	/**
	 * Strip line-breaks via callback
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	protected function removeNLCallback($matches)
	{
		if (strpos($matches[1], "\r") !== false || strpos($matches[1], "\n") !== false)
		{
			$matches[1] = str_replace(["\r\n", "\r", "\n"], '', $matches[1]);
		}

		return $matches[1];
	}

	//--------------------------------------------------------------------

	/**
	 * Spool mail to the mail server
	 *
	 * @return boolean
	 */
	protected function spoolEmail()
	{
		$this->unwrapSpecials();

		$protocol = $this->getProtocol();
		$method   = 'sendWith' . ucfirst($protocol);
		try
		{
			$success = $this->$method();
		}
		catch (\ErrorException $e)
		{
			$success = false;
			$this->logger->error('Messenger: ' . $method . ' throwed ' . $e->getMessage());
		}

		if (! $success)
		{
				throw MessengerException::forSendFailure($protocol === 'mail' ? 'PHPMail' : ucfirst($protocol));
		}

		$this->setErrorMessage(lang('Messenger.sent', [$protocol]));

		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * Validate email for shell
	 *
	 * Applies stricter, shell-safe validation to email addresses.
	 * Introduced to prevent RCE via sendmail's -f option.
	 *
	 * @see     https://github.com/codeigniter4/CodeIgniter/issues/4963
	 * @see     https://gist.github.com/Zenexer/40d02da5e07f151adeaeeaa11af9ab36
	 * @license https://creativecommons.org/publicdomain/zero/1.0/    CC0 1.0, Public Domain
	 *
	 * Credits for the base concept go to Paul Buonopane <paul@namepros.com>
	 *
	 * @param string &$email
	 *
	 * @return boolean
	 */
	protected function validateEmailForShell(&$email)
	{
		if (function_exists('idn_to_ascii') && $atpos = strpos($email, '@'))
		{
			$email = static::substr($email, 0, ++ $atpos) . idn_to_ascii(
							static::substr($email, $atpos), 0, INTL_IDNA_VARIANT_UTS46
			);
		}

		return (filter_var($email, FILTER_VALIDATE_EMAIL) === $email && preg_match('#\A[a-z0-9._+-]+@[a-z0-9.-]{1,253}\z#i', $email));
	}

	//--------------------------------------------------------------------

	/**
	 * Send using mail()
	 *
	 * @return boolean
	 */
	protected function sendWithMail()
	{
		if (is_array($this->recipients))
		{
			$this->recipients = implode(', ', $this->recipients);
		}

		// _validate_email_for_shell() below accepts by reference,
		// so this needs to be assigned to a variable
		$from = $this->cleanEmail($this->headers['Return-Path']);

		if (! $this->validateEmailForShell($from))
		{
			return mail($this->recipients, $this->subject, $this->finalBody, $this->headerStr);
		}

		// most documentation of sendmail using the "-f" flag lacks a space after it, however
		// we've encountered servers that seem to require it to be in place.
		return mail($this->recipients, $this->subject, $this->finalBody, $this->headerStr, '-f ' . $from);
	}

	//--------------------------------------------------------------------

	/**
	 * Send using Sendmail
	 *
	 * @return boolean
	 */
	protected function sendWithSendmail()
	{
		// _validate_email_for_shell() below accepts by reference,
		// so this needs to be assigned to a variable
		$from = $this->cleanEmail($this->headers['From']);
		if ($this->validateEmailForShell($from))
		{
			$from = '-f ' . $from;
		}
		else
		{
			$from = '';
		}

		// is popen() enabled?
		if (! function_usable('popen') || false === ($fp = @popen($this->mailPath . ' -oi ' . $from . ' -t', 'w')))
		{
			// server probably has popen disabled, so nothing we can do to get a verbose error.
			return false;
		}

		fputs($fp, $this->headerStr);
		fputs($fp, $this->finalBody);

		$status = pclose($fp);

		if ($status !== 0)
		{
				throw MessengerException::forNosocket($status);
		}

		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * Send using SMTP
	 *
	 * @return boolean
	 */
	protected function sendWithSmtp()
	{
		if ($this->SMTPHost === '')
		{
				throw MessengerException::forNoHostname();
		}

		if (! $this->SMTPConnect() || ! $this->SMTPAuthenticate())
		{
			return false;
		}

		if (! $this->sendCommand('from', $this->cleanEmail($this->headers['From'])))
		{
			$this->SMTPEnd();

			return false;
		}

		foreach ($this->recipients as $val)
		{
			if (! $this->sendCommand('to', $val))
			{
				$this->SMTPEnd();

				return false;
			}
		}

		foreach ($this->CCArray as $val)
		{
			if ($val !== '' && ! $this->sendCommand('to', $val))
			{
				$this->SMTPEnd();

				return false;
			}
		}

		foreach ($this->BCCArray as $val)
		{
			if ($val !== '' && ! $this->sendCommand('to', $val))
			{
				$this->SMTPEnd();

				return false;
			}
		}

		if (! $this->sendCommand('data'))
		{
			$this->SMTPEnd();

			return false;
		}

		// perform dot transformation on any lines that begin with a dot
		$this->sendData($this->headerStr . preg_replace('/^\./m', '..$1', $this->finalBody));

		$this->sendData('.');
		$reply = $this->getSMTPData();
		$this->setErrorMessage($reply);

		$this->SMTPEnd();

		if (strpos($reply, '250') !== 0)
		{
				throw MessengerException::forSMTPError($reply);
		}

		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * SMTP End
	 *
	 * Shortcut to send RSET or QUIT depending on keep-alive
	 */
	protected function SMTPEnd()
	{
		$this->sendCommand($this->SMTPKeepAlive ? 'reset' : 'quit');
	}

	//--------------------------------------------------------------------

	/**
	 * SMTP Connect
	 *
	 * @return string
	 */
	protected function SMTPConnect()
	{
		if (is_resource($this->SMTPConnect))
		{
			return true;
		}

		$ssl = ($this->SMTPCrypto === 'ssl') ? 'ssl://' : '';

		$this->SMTPConnect = fsockopen(
				$ssl . $this->SMTPHost, $this->SMTPPort, $errno, $errstr, $this->SMTPTimeout
		);

		if (! is_resource($this->SMTPConnect))
		{
				throw MessengerException::forSMTPError($errno . ' ' . $errstr);
		}

		stream_set_timeout($this->SMTPConnect, $this->SMTPTimeout);
		$this->setErrorMessage($this->getSMTPData());

		if ($this->SMTPCrypto === 'tls')
		{
			$this->sendCommand('hello');
			$this->sendCommand('starttls');

			$crypto = stream_socket_enable_crypto($this->SMTPConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

			if ($crypto !== true)
			{
				throw MessengerException::forSMTPError($this->getSMTPData());
			}
		}

		return $this->sendCommand('hello');
	}

	//--------------------------------------------------------------------

	/**
	 * Send SMTP command
	 *
	 * @param string $cmd
	 * @param string $data
	 *
	 * @return boolean
	 */
	protected function sendCommand($cmd, $data = '')
	{
		switch ($cmd)
		{
			case 'hello':
				if ($this->SMTPAuth || $this->getEncoding() === '8bit')
				{
					$this->sendData('EHLO ' . $this->getHostname());
				}
				else
				{
					$this->sendData('HELO ' . $this->getHostname());
				}

				$resp = 250;
				break;
			case 'starttls':
				$this->sendData('STARTTLS');
				$resp = 220;
				break;
			case 'from':
				$this->sendData('MAIL FROM:<' . $data . '>');
				$resp = 250;
				break;
			case 'to':
				if ($this->DSN)
				{
					$this->sendData('RCPT TO:<' . $data . '> NOTIFY=SUCCESS,DELAY,FAILURE ORCPT=rfc822;' . $data);
				}
				else
				{
					$this->sendData('RCPT TO:<' . $data . '>');
				}
				$resp = 250;
				break;
			case 'data':
				$this->sendData('DATA');
				$resp = 354;
				break;
			case 'reset':
				$this->sendData('RSET');
				$resp = 250;
				break;
			case 'quit':
				$this->sendData('QUIT');
				$resp = 221;
				break;
		}

		$reply = $this->getSMTPData();

		$this->debugMessage[] = '<pre>' . $cmd . ': ' . $reply . '</pre>';

		if ((int) static::substr($reply, 0, 3) !== $resp)
		{
				throw MessengerException::forSMTPError($reply);
		}

		if ($cmd === 'quit')
		{
			fclose($this->SMTPConnect);
		}

		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * SMTP Authenticate
	 *
	 * @return boolean
	 */
	protected function SMTPAuthenticate()
	{
		if (! $this->SMTPAuth)
		{
			return true;
		}

		if ($this->SMTPUser === '' && $this->SMTPPass === '')
		{
				throw MessengerException::forNoSMTPAuth();
		}

		$this->sendData('AUTH LOGIN');
		$reply = $this->getSMTPData();

		if (strpos($reply, '503') === 0)    // Already authenticated
		{
			return true;
		}
		elseif (strpos($reply, '334') !== 0)
		{
				throw MessengerException::forFailedSMTPLogin($reply);
		}

		$this->sendData(base64_encode($this->SMTPUser));
		$reply = $this->getSMTPData();

		if (strpos($reply, '334') !== 0)
		{
				throw MessengerException::forSMTPAuthUsername($reply);
		}

		$this->sendData(base64_encode($this->SMTPPass));
		$reply = $this->getSMTPData();

		if (strpos($reply, '235') !== 0)
		{
				throw MessengerException::forSMTPAuthPassword($reply);
		}

		if ($this->SMTPKeepAlive)
		{
			$this->SMTPAuth = false;
		}

		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * Send SMTP data
	 *
	 * @param string $data
	 *
	 * @return boolean
	 */
	protected function sendData($data)
	{
		$data .= $this->newline;
		for ($written = $timestamp = 0, $length = static::strlen($data); $written < $length; $written += $result)
		{
			if (($result = fwrite($this->SMTPConnect, static::substr($data, $written))) === false)
			{
				break;
			}
			// See https://bugs.php.net/bug.php?id=39598 and http://php.net/manual/en/function.fwrite.php#96951
			elseif ($result === 0)
			{
				if ($timestamp === 0)
				{
					$timestamp = time();
				}
				elseif ($timestamp < (time() - $this->SMTPTimeout))
				{
					$result = false;
					break;
				}

				usleep(250000);
				continue;
			}
			else
			{
				$timestamp = 0;
			}
		}

		if ($result === false)
		{
				throw MessengerException::forSMTPDataFailure($data);
		}

		return true;
	}

	//--------------------------------------------------------------------

	/**
	 * Get SMTP data
	 *
	 * @return string
	 */
	protected function getSMTPData()
	{
		$data = '';

		while ($str = fgets($this->SMTPConnect, 512))
		{
			$data .= $str;

			if ($str[3] === ' ')
			{
				break;
			}
		}

		return $data;
	}

	//--------------------------------------------------------------------

	/**
	 * Get Hostname
	 *
	 * There are only two legal types of hostname - either a fully
	 * qualified domain name (eg: "mail.example.com") or an IP literal
	 * (eg: "[1.2.3.4]").
	 *
	 * @link https://tools.ietf.org/html/rfc5321#section-2.3.5
	 * @link http://cbl.abuseat.org/namingproblems.html
	 *
	 * @return string
	 */
	protected function getHostname()
	{
		if (isset($_SERVER['SERVER_NAME']))
		{
			return $_SERVER['SERVER_NAME'];
		}

		return isset($_SERVER['SERVER_ADDR']) ? '[' . $_SERVER['SERVER_ADDR'] . ']' : '[127.0.0.1]';
	}

	//--------------------------------------------------------------------

	/**
	 * Get Debug Message
	 *
	 * @param array $include List of raw data chunks to include in the output
	 *                       Valid options are: 'headers', 'subject', 'body'
	 *
	 * @return string
	 */
	public function printDebugger($include = ['headers', 'subject', 'body'])
	{
		$msg = implode('', $this->debugMessage);

		// Determine which parts of our raw data needs to be printed
		$raw_data                      = '';
		is_array($include) || $include = [$include];

		in_array('headers', $include, true) && $raw_data  = htmlspecialchars($this->headerStr) . "\n";
		in_array('subject', $include, true) && $raw_data .= htmlspecialchars($this->subject) . "\n";
		in_array('body', $include, true) && $raw_data    .= htmlspecialchars($this->finalBody);

		return $msg . ($raw_data === '' ? '' : '<pre>' . $raw_data . '</pre>');
	}

	//--------------------------------------------------------------------

	/**
	 * Set Message
	 *
	 * @param string $msg
	 */
	protected function setErrorMessage($msg)
	{
		$this->debugMessage[] = $msg . '<br />';
	}

	//--------------------------------------------------------------------

	/**
	 * Mime Types
	 *
	 * @param string $ext
	 *
	 * @return string
	 */
	protected function mimeTypes($ext = '')
	{
		$mime = Mimes::guessTypeFromExtension(strtolower($ext));

		return ! empty($mime) ? $mime : 'application/x-unknown-content-type';
	}

	//--------------------------------------------------------------------

	/**
	 * Destructor
	 */
	public function __destruct()
	{
		is_resource($this->SMTPConnect) && $this->sendCommand('quit');
	}

	//--------------------------------------------------------------------

	/**
	 * Byte-safe strlen()
	 *
	 * @param string $str
	 *
	 * @return integer
	 */
	protected static function strlen($str)
	{
		return (static::$func_overload) ? mb_strlen($str, '8bit') : strlen($str);
	}

	//--------------------------------------------------------------------

	/**
	 * Byte-safe substr()
	 *
	 * @param string       $str
	 * @param integer      $start
	 * @param integer|null $length
	 *
	 * @return string
	 */
	protected static function substr($str, $start, $length = null)
	{
		if (static::$func_overload)
		{
			return mb_substr($str, $start, $length, '8bit');
		}

		return isset($length) ? substr($str, $start, $length) : substr($str, $start);
	}

}
