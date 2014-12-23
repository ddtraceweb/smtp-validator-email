<?php


namespace SmtpValidatorEmail\Tests\Email;


use SmtpValidatorEmail\Helper\BagHelper;

class BagHelperTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorNoArrayGivenDropsError()
    {
        $this->setExpectedException(get_class(new \PHPUnit_Framework_Error("", 0, "", 1)));
        new BagHelper("notAnArray");
    }

    public function testToStringReturnsString()
    {
        $emails = array (
            'some@mail.com',
            'some1@mail.com',
            'some2@mail.com',
            'some3@mail.com',
            'some4@mail.com'
        );

        $bag = new BagHelper($emails);
        $this->assertTrue(is_string($bag->__toString()));
    }

    public function testReplaceNoArrayGivenDropsError()
    {
        $this->setExpectedException(get_class(new \PHPUnit_Framework_Error("", 0, "", 1)));
        $bag = new BagHelper();
        $bag->replace("sdasd");
    }

    public function testGetReturnsProperValue()
    {
        $emails = array (
            'some@mail.com',
            'some1@mail.com',
            'some2@mail.com',
            'some3@mail.com',
            'some4@mail.com'
        );

        $bag = new BagHelper($emails);
        $this->assertEquals('some3@mail.com', $bag->get(3));
    }

    public function testGetInvalidArgumentKey()
    {
        $this->setExpectedException(
            '\InvalidArgumentException'
        );
        $bag = new BagHelper();
        $bag->get(new \stdClass());
    }

    public function testGetWorksWithAssocArray()
    {
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );

        $bag = new BagHelper($emails);
        $bag->get('codeforges');
    }

    public function testSetInvalidArgumentKey()
    {
        $this->setExpectedException(
            '\InvalidArgumentException'
        );

        $bag = new BagHelper();
        $bag->set(new \stdClass(), 'email@dev.com');
    }

    public function testSetAcceptsAssocArray()
    {
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );

        new BagHelper($emails);
    }

    public function testHasReturnProperValue()
    {
        $emails = array (
            'some@mail.com',
            'some1@mail.com',
            'some2@mail.com',
            'some3@mail.com',
            'some4@mail.com'
        );
        $bag = new BagHelper($emails);
        $this->assertTrue($bag->has(1));
    }

    public function testHasWorksWithAssocArray()
    {
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );

        $bag = new BagHelper($emails);
        $this->assertTrue($bag->has('codeforges'));
    }

    public function testContainsReturnsPropperValue()
    {
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );

        $bag = new BagHelper($emails);
        $this->assertTrue($bag->contains('codeforges', 'ruben@codeforges.com'));
    }

    public function testRemove()
    {
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );

        $bag = new BagHelper($emails);
        $beforeRemoveEmails = $bag->all();
        $bag->remove('codeforges');
        $this->assertNotEquals($beforeRemoveEmails, $bag->all());
    }

    public function testReplace()
    {
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );

        $replaceEmails = array (
            'traceweb' => 'david.djian@traceweb.fr',
            'codeforges' => 'ruben@codeforges.com'
        );

        $bag = new BagHelper($emails);
        $oldEmails = $bag->all();
        $bag->replace($replaceEmails);
        $replaceEmails = $bag->all();

        $this->assertNotTrue($oldEmails === $replaceEmails);
    }

    public function testGetIteratorIsObject(){
        $emails = array (
            'codeforges' => 'ruben@codeforges.com',
            'traceweb' => 'david.djian@traceweb.fr'
        );
        $bag = new BagHelper($emails);

        $this->assertTrue( is_object($bag->getIterator()) );
    }
}
