
var QueueBaseCommand = function(commandPkg)
{
    var self = this;

    self.command = commandPkg;

    self.then = function(atResolve, atReject, atProgress)
    {
        if (atResolve)
            self.command['S']['+'] = atResolve.getCommandData();
        if ($atReject)
            self.command['S']['!'] = atReject.getCommandData();
        if ($atProgress)
            self.command['S']['~'] = atProgress.getCommandData();
    }

    self.getCommandData = function()
    {
        return self.command;
    }
}

var QueueClient = function()
{
    var self = this;

    self.commandId = 0;
    self.cmdList = {};

    self.onMessage = function(onMessage)
    {
        self.onMessage = onMessage;
    };

    // trace all messages in the queue
    self.trace  = function (queueId, chanel)
    {
        return self.send(
            new QueueBaseCommand({'^':'TRACE', 'q':queueId, 'L':chanel})
        );
    }

    self.send = function(obj, data)
    {
        self.commandId++;
        command = obj.getCommandData();

        command['#'] = self.commandId;
        var deferred = $.Deferred();

        self.cmdList[self.commandId] = deferred;
        self.onMessage(
            JSON.stringify(command) + '\r\n' +
            JSON.stringify(data)
        );
        return deferred.promise();
    };

    self.settle = function(data)
    {
        return true;
    }

    self.receive = function(data)
    {
        lines = data.match(/[^\r\n]+/g);
        cmd = JSON.parse(lines[0]);
        if (('#' in cmd) && (cmd['#'] in self.cmdList))
        {
            wait = self.cmdList[cmd['#']];
            if (self.settle(wait, cmd, JSON.parse(lines[1])))
            {
                delete self.cmdList[cmd['#']];
            }
        }
        else
        {
            // unknown command ID arrived, nothing to do, could write error?
        }
    }

}

