var myCode  = require('./functions')

describe('tests', function(){
    describe('testFunction', function(){
        it('should return 1', function(){
            myCode.testFunction().should.equal(1);
        })
    })
})