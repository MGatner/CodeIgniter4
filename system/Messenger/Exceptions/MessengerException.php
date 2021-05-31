<?php

/**
 * This file is part of the CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CodeIgniter\Messenger\Exceptions;

use CodeIgniter\Exceptions\FrameworkException;

class MessengerException extends FrameworkException
{
	public static function forAttachmentMissing(string $file = null)
	{
		return new static(lang('Messenger.attachmentMissing', [$file]));
	}

	public static function forAttachmentUnreadable(string $file = null)
	{
		return new static(lang('Messenger.attachmentUnreadable', [$file]));
	}

	public static function forMustBeArray()
	{
		return new static(lang('Messenger.mustBeArray', []));
	}

	public static function forInvalidAddress(string $value = null)
	{
		return new static(lang('Messenger.invalidAddress', [$value]));
	}

	public static function forInvalidProtocol(string $value = null)
	{
		return new static(lang('Messenger.invalidProtocolRequested', [$value]));
	}

	public static function forNoFrom()
	{
		return new static(lang('Messenger.noFrom', []));
	}

	public static function forNoRecipients()
	{
		return new static(lang('Messenger.noRecipients', []));
	}

	public static function forSendFailure(string $protocol = '?')
	{
		return new static(lang('Messenger.SendFailure', [$protocol]));
	}

	public static function forNosocket(string $status = '?')
	{
		return new static(lang('Messenger.exitStatus', [$status]) . lang('Messenger.nosocket', []));
	}

	public static function forNoHostname()
	{
		return new static(lang('Messenger.noHostname', []));
	}

	public static function forSMTPError(string $reply = '?')
	{
		return new static(lang('Messenger.SMTPError', [$reply]));
	}

	public static function forNoSMTPAuth()
	{
		return new static(lang('Messenger.noSMTPAuth', []));
	}

	public static function forFailedSMTPLogin(string $reply = '?')
	{
		return new static(lang('Messenger.failedSMTPLogin', [$reply]));
	}

	public static function forSMTPAuthUsername(string $reply = '?')
	{
		return new static(lang('Messenger.SMTPAuthUsername', [$reply]));
	}

	public static function forSMTPAuthPassword(string $reply = '?')
	{
		return new static(lang('Messenger.SMTPAuthPassword', [$reply]));
	}

	public static function forSMTPDataFailure(string $data = '?')
	{
		return new static(lang('Messenger.SMTPDataFailure', [$data]));
	}
}
