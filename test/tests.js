var myCode  = require('./functions')

describe('tests', function(){
    describe('testFunction', function(){
        it('should return 1', function(){
            testFunction().should.equal(1);
        })
    })
})