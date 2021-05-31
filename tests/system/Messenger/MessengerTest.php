<?php namespace CodeIgniter\Messenger;

use CodeIgniter\Test\CIUnitTestCase;

class MessengerTest extends CIUnitTestCase
{
	public function tearDown()
	{
		// Restore file permissions after unreadable attachment test
		$thefile = SUPPORTPATH . 'Messenger/ci-logo-not-readable.png';
		chmod($thefile, 0664);
	}

	//--------------------------------------------------------------------
	// Test constructor & configs

	public function testDefaultWithCustomConfig()
	{
		$messenger = new Messenger(['validate' => true, 'truth' => 'out there']);
		$this->assertTrue($messenger->wordWrap);
		$this->assertEquals(76, $messenger->wrapChars);
		$this->assertEquals('text', $messenger->mailType);
		$this->assertEquals('UTF-8', $messenger->charset);
		$this->assertEquals('', $messenger->altMessage);
		$this->assertTrue($messenger->validate);
		$this->assertNull($messenger->truth);
	}

	public function testDefaultWithEmptyConfig()
	{
		$messenger = new Messenger();
		$this->assertTrue($messenger->wordWrap);
		$this->assertEquals(76, $messenger->wrapChars);
		$this->assertEquals('text', $messenger->mailType);
		$this->assertEquals('UTF-8', $messenger->charset);
		$this->assertEquals('', $messenger->altMessage);
		$this->assertFalse($messenger->validate); // this one differs
		$this->assertNull($messenger->truth);
	}

	//--------------------------------------------------------------------
	// Test setting the "from" property

	public function testSetFromMessengerOnly()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$this->assertEquals(' <leia@alderaan.org>', $messenger->getHeader('From'));
		$this->assertEquals('<leia@alderaan.org>', $messenger->getHeader('Return-Path'));
	}

	public function testSetFromMessengerAndName()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org', 'Princess Leia');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('From'));
		$this->assertEquals('<leia@alderaan.org>', $messenger->getHeader('Return-Path'));
	}

	public function testSetFromMessengerAndFunkyName()
	{
		$messenger = new Messenger();
		$messenger->setFrom('<leia@alderaan.org>', 'Princess Leià');
		$this->assertEquals('=?UTF-8?Q?Princess=20Lei=C3=A0?= <leia@alderaan.org>', $messenger->getHeader('From'));
		$this->assertEquals('<leia@alderaan.org>', $messenger->getHeader('Return-Path'));
	}

	public function testSetFromWithValidation()
	{
		$messenger = new Messenger(['validation' => true]);
		$messenger->setFrom('leia@alderaan.org', 'Princess Leia');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('From'));
		$this->assertEquals('<leia@alderaan.org>', $messenger->getHeader('Return-Path'));
	}

	public function testSetFromWithValidationAndReturnPath()
	{
		$messenger = new Messenger(['validation' => true]);
		$messenger->setFrom('leia@alderaan.org', 'Princess Leia', 'leia@alderaan.org');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('From'));
		$this->assertEquals('<leia@alderaan.org>', $messenger->getHeader('Return-Path'));
	}

	public function testSetFromWithValidationAndDifferentReturnPath()
	{
		$messenger = new Messenger(['validation' => true]);
		$messenger->setFrom('leia@alderaan.org', 'Princess Leia', 'padme@naboo.org');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('From'));
		$this->assertEquals('<padme@naboo.org>', $messenger->getHeader('Return-Path'));
	}

	//--------------------------------------------------------------------
	// Test setting the "replyTo" property

	public function testSetReplyToMessengerOnly()
	{
		$messenger = new Messenger();
		$messenger->setReplyTo('leia@alderaan.org');
		$this->assertEquals(' <leia@alderaan.org>', $messenger->getHeader('Reply-To'));
	}

	public function testSetReplyToMessengerAndName()
	{
		$messenger = new Messenger();
		$messenger->setReplyTo('leia@alderaan.org', 'Princess Leia');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('Reply-To'));
	}

	public function testSetReplyToMessengerAndFunkyName()
	{
		$messenger = new Messenger();
		$messenger->setReplyTo('<leia@alderaan.org>', 'Princess Leià');
		$this->assertEquals('=?UTF-8?Q?Princess=20Lei=C3=A0?= <leia@alderaan.org>', $messenger->getHeader('Reply-To'));
	}

	public function testSetReplyToWithValidation()
	{
		$messenger = new Messenger(['validation' => true]);
		$messenger->setReplyTo('leia@alderaan.org', 'Princess Leia');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('Reply-To'));
	}

	public function testSetReplyToWithValidationAndReturnPath()
	{
		$messenger = new Messenger(['validation' => true]);
		$messenger->setReplyTo('leia@alderaan.org', 'Princess Leia', 'leia@alderaan.org');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('Reply-To'));
	}

	public function testSetReplyToWithValidationAndDifferentReturnPath()
	{
		$messenger = new Messenger(['validation' => true]);
		$messenger->setReplyTo('leia@alderaan.org', 'Princess Leia', 'padme@naboo.org');
		$this->assertEquals('"Princess Leia" <leia@alderaan.org>', $messenger->getHeader('Reply-To'));
	}

	//--------------------------------------------------------------------
	// Test setting the "to" property (recipients)

	public function testSetToBasic()
	{
		$messenger = new Messenger();
		$messenger->setTo('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->recipients));
	}

	public function testSetToArray()
	{
		$messenger = new Messenger();
		$messenger->setTo(['Luke <luke@tatooine.org>', 'padme@naboo.org']);
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->recipients));
		$this->assertTrue(in_array('padme@naboo.org', $messenger->recipients));
	}

	public function testSetToValid()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setTo('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->recipients));
	}

	public function testSetToInvalid()
	{
		$messenger = new Messenger(['validate' => false]);
		$messenger->setTo('Luke <luke@tatooine>');
		$this->assertTrue(in_array('luke@tatooine', $messenger->recipients));
	}

	/**
	 * @expectedException \CodeIgniter\Messenger\Exceptions\MessengerException
	 */
	public function testDontSetToInvalid()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setTo('Luke <luke@tatooine>');
	}

	public function testSetToHeader()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setProtocol('sendmail');
		$messenger->setTo('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->recipients));
		$this->assertEquals('luke@tatooine.org', $messenger->getHeader('To'));
	}

	//--------------------------------------------------------------------
	// Test setting the "cc" property (copied recipients)

	public function testSetCCBasic()
	{
		$messenger = new Messenger();
		$messenger->setCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->CCArray));
	}

	public function testSetCCArray()
	{
		$messenger = new Messenger();
		$messenger->setCC(['Luke <luke@tatooine.org>', 'padme@naboo.org']);
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->CCArray));
		$this->assertTrue(in_array('padme@naboo.org', $messenger->CCArray));
		$this->assertEquals('luke@tatooine.org, padme@naboo.org', $messenger->getHeader('Cc'));
	}

	public function testSetCCValid()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->CCArray));
	}

	public function testSetCCInvalid()
	{
		$messenger = new Messenger(['validate' => false]);
		$messenger->setCC('Luke <luke@tatooine>');
		$this->assertTrue(in_array('luke@tatooine', $messenger->CCArray));
	}

	/**
	 * @expectedException \CodeIgniter\Messenger\Exceptions\MessengerException
	 */
	public function testDontSetCCInvalid()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setCC('Luke <luke@tatooine>');
	}

	public function testSetCCHeader()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->CCArray));
		$this->assertEquals('luke@tatooine.org', $messenger->getHeader('Cc'));
	}

	public function testSetCCForSMTP()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setProtocol('smtp');
		$messenger->setCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->CCArray));
		$this->assertEquals('luke@tatooine.org', $messenger->getHeader('Cc'));
	}

	//--------------------------------------------------------------------
	// Test setting the "bcc" property (blind-copied recipients)

	public function testSetBCCBasic()
	{
		$messenger = new Messenger();
		$messenger->setBCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
	}

	public function testSetBCCArray()
	{
		$messenger = new Messenger();
		$messenger->setBCC(['Luke <luke@tatooine.org>', 'padme@naboo.org']);
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
		$this->assertTrue(in_array('padme@naboo.org', $messenger->BCCArray));
		$this->assertEquals('luke@tatooine.org, padme@naboo.org', $messenger->getHeader('Bcc'));
	}

	public function testSetBCCValid()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setBCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
	}

	public function testSetBCCInvalid()
	{
		$messenger = new Messenger(['validate' => false]);
		$messenger->setBCC('Luke <luke@tatooine>');
		$this->assertTrue(in_array('luke@tatooine', $messenger->BCCArray));
	}

	/**
	 * @expectedException \CodeIgniter\Messenger\Exceptions\MessengerException
	 */
	public function testDontSetBCCInvalid()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setBCC('Luke <luke@tatooine>');
	}

	public function testSetBCCHeader()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setBCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
		$this->assertEquals('luke@tatooine.org', $messenger->getHeader('Bcc'));
	}

	public function testSetBCCForSMTP()
	{
		$messenger = new Messenger(['validate' => true]);
		$messenger->setProtocol('smtp');
		$messenger->setBCC('Luke <luke@tatooine.org>');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
		$this->assertEquals('luke@tatooine.org', $messenger->getHeader('Bcc'));
	}

	public function testSetBCCBatch()
	{
		$messenger = new Messenger();
		$messenger->setBCC(['Luke <luke@tatooine.org>', 'padme@naboo.org'], 2);
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
		$this->assertTrue(in_array('padme@naboo.org', $messenger->BCCArray));
		$this->assertEquals('luke@tatooine.org, padme@naboo.org', $messenger->getHeader('Bcc'));
	}

	public function testSetBCCBiggerBatch()
	{
		$messenger = new Messenger();
		$messenger->setBCC(['Luke <luke@tatooine.org>', 'padme@naboo.org', 'leia@alderaan.org'], 2);
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->BCCArray));
		$this->assertTrue(in_array('padme@naboo.org', $messenger->BCCArray));
		$this->assertEquals('luke@tatooine.org, padme@naboo.org, leia@alderaan.org', $messenger->getHeader('Bcc'));
	}

	//--------------------------------------------------------------------
	// Test setting the subject

	public function testSetSubject()
	{
		$messenger    = new Messenger();
		$original = 'Just a silly love song';
		$expected = '=?UTF-8?Q?Just=20a=20silly=20love=20so?==?UTF-8?Q?ng?=';
		$messenger->setSubject($original);
		$this->assertEquals($expected, $messenger->getHeader('Subject'));
	}

	public function testSetEncodedSubject()
	{
		$messenger    = new Messenger();
		$original = 'Just a silly Leià song';
		$expected = '=?UTF-8?Q?Just=20a=20silly=20Lei=C3=A0=20s?==?UTF-8?Q?ong?=';
		$messenger->setSubject($original);
		$this->assertEquals($expected, $messenger->getHeader('Subject'));
	}

	//--------------------------------------------------------------------
	// Test setting the body

	public function testSetMessage()
	{
		$messenger    = new Messenger();
		$original = 'Just a silly love song';
		$expected = $original;
		$messenger->setMessage($original);
		$this->assertEquals($expected, $messenger->body);
	}

	public function testSetMultilineMessage()
	{
		$messenger    = new Messenger();
		$original = "Just a silly love song\r\nIt's just two lines long";
		$expected = "Just a silly love song\nIt's just two lines long";
		$messenger->setMessage($original);
		$this->assertEquals($expected, $messenger->body);
	}

	//--------------------------------------------------------------------
	// Test setting the alternate message

	public function testSetAltMessage()
	{
		$messenger    = new Messenger();
		$original = 'Just a silly love song';
		$expected = $original;
		$messenger->setAltMessage($original);
		$this->assertEquals($expected, $messenger->altMessage);
	}

	public function testSetMultilineAltMessage()
	{
		$messenger    = new Messenger();
		$original = "Just a silly love song\r\nIt's just two lines long";
		$messenger->setAltMessage($original);
		$this->assertEquals($original, $messenger->altMessage);
	}

	//--------------------------------------------------------------------
	// Test clearing the email

	public function testClearing()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$this->assertEquals(' <leia@alderaan.org>', $messenger->getHeader('From'));
		$messenger->setTo('luke@tatooine.org');
		$this->assertTrue(in_array('luke@tatooine.org', $messenger->recipients));

		$messenger->clear(true);
		$this->assertEquals('', $messenger->getHeader('From'));
		$this->assertEquals('', $messenger->getHeader('To'));

		$messenger->setFrom('leia@alderaan.org');
		$this->assertEquals(' <leia@alderaan.org>', $messenger->getHeader('From'));
	}

	//--------------------------------------------------------------------
	// Test clearing the email

	public function testAttach()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$messenger->setTo('luke@tatooine.org');

		$messenger->attach(SUPPORTPATH . 'Images/ci-logo.png');
		$this->assertEquals(1, count($messenger->attachments));
	}

	/**
	 * @expectedException \CodeIgniter\Messenger\Exceptions\MessengerException
	 */
	public function testAttachNotThere()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$messenger->setTo('luke@tatooine.org');

		$messenger->attach(SUPPORTPATH . 'Messenger/ci-logo-not-there.png');
		$this->assertEquals(1, count($messenger->attachments));
	}

	/**
	 * @expectedException \CodeIgniter\Messenger\Exceptions\MessengerException
	 */
	public function testAttachNotReadable()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$messenger->setTo('luke@tatooine.org');

		$thefile = SUPPORTPATH . 'Messenger/ci-logo-not-readable.png';
		chmod($thefile, 0222);
		$messenger->attach($thefile);
	}

	public function testAttachContent()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$messenger->setTo('luke@tatooine.org');

		$content = 'This is bogus content';
		$messenger->attach($content, '', 'truelies.txt', 'text/html');
		$this->assertEquals(1, count($messenger->attachments));
	}

	//--------------------------------------------------------------------
	// Test changing the protocol

	public function testSetProtocol()
	{
		$messenger = new Messenger();
		$this->assertEquals('mail', $messenger->getProtocol()); // default
		$messenger->setProtocol('smtp');
		$this->assertEquals('smtp', $messenger->getProtocol());
		$messenger->setProtocol('mail');
		$this->assertEquals('mail', $messenger->getProtocol());
	}

	/**
	 * @expectedException \CodeIgniter\Messenger\Exceptions\MessengerException
	 */
	public function testSetBadProtocol()
	{
		$messenger = new Messenger();
		$messenger->setProtocol('mind-reader');
	}

	//--------------------------------------------------------------------
	// Test word wrap

	public function testWordWrapVanilla()
	{
		$messenger    = new Messenger();
		$original = 'This is a short line.';
		$expected = $original;
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original)));
	}

	public function testWordWrapShortLines()
	{
		$messenger    = new Messenger();
		$original = 'This is a short line.';
		$expected = "This is a short\r\nline.";
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original, 16)));
	}

	public function testWordWrapLines()
	{
		$messenger    = new Messenger();
		$original = "This is a\rshort line.";
		$expected = "This is a\r\nshort line.";
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original)));
	}

	public function testWordWrapUnwrap()
	{
		$messenger    = new Messenger();
		$original = 'This is a {unwrap}not so short{/unwrap} line.';
		$expected = 'This is a not so short line.';
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original)));
	}

	public function testWordWrapUnwrapWrapped()
	{
		$messenger    = new Messenger();
		$original = 'This is a {unwrap}not so short or something{/unwrap} line.';
		$expected = "This is a\r\nnot so short or something\r\nline.";
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original, 16)));
	}

	public function testWordWrapConsolidate()
	{
		$messenger    = new Messenger();
		$original = "This is\r\na not so short or something\r\nline.";
		$expected = "This is\r\na not so short\r\nor something\r\nline.";
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original, 16)));
	}

	public function testWordWrapLongWord()
	{
		$messenger    = new Messenger();
		$original = "This is part of interoperabilities isn't it?";
		$expected = "This is part of\r\ninteroperabilit\r\nies\r\nisn't it?";
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original, 16)));
	}

	public function testWordWrapURL()
	{
		$messenger    = new Messenger();
		$original = "This is part of http://interoperabilities.com isn't it?";
		$expected = "This is part of\r\nhttp://interoperabilities.com\r\nisn't it?";
		$this->assertEquals($expected, rtrim($messenger->wordWrap($original, 16)));
	}

	//--------------------------------------------------------------------
	// Test support methods

	public function testValidMessenger()
	{
		$messenger = new Messenger();
		$this->assertTrue($messenger->isValidMessenger('"Princess Leia" <leia@alderaan.org>'));
		$this->assertTrue($messenger->isValidMessenger('leia@alderaan.org'));
		$this->assertTrue($messenger->isValidMessenger('<princess.leia@alderaan.org>'));
		$this->assertFalse($messenger->isValidMessenger('<leia_at_alderaan.org>'));
		$this->assertFalse($messenger->isValidMessenger('<leia@alderaan>'));
		$this->assertFalse($messenger->isValidMessenger('<leia.alderaan@org>'));
	}

	public function testMagicMethods()
	{
		$messenger           = new Messenger();
		$messenger->protocol = 'mail';
		$this->assertEquals('mail', $messenger->protocol);
	}

	//--------------------------------------------------------------------
	// "Test" sending the email

	public function testFakeSend()
	{
		$messenger = new Messenger();
		$messenger->setFrom('leia@alderaan.org');
		$messenger->setTo('Luke <luke@tatooine>');
		$messenger->setSubject('Hi there');

		// make sure the second parameter below is "false"
		// or you will trigger email for real!
		$this->assertTrue($messenger->send(true, false));
	}
}
