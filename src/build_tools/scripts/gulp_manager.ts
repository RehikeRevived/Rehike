/**
 * @fileoverview Manages Gulp use in RehikeBuild.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import gulp, { Gulp, TaskFunction } from "gulp";
import chalk from "chalk";

// https://github.com/gulpjs/undertaker/blob/2d95b5273d6a61fd4ca09376e91faae1045bbbe2/lib/helpers/createExtensions.js#L36-L40
interface GulpStartEvent
{
    uid: number;
    name: string;
    branch: string;
    time: number;
}

// https://github.com/gulpjs/undertaker/blob/2d95b5273d6a61fd4ca09376e91faae1045bbbe2/lib/helpers/createExtensions.js#L48-L53
interface GulpStopEvent
{
    uid: number;
    name: string;
    branch: string;
    duration: number[];
    time: number;
}

// https://github.com/gulpjs/undertaker/blob/2d95b5273d6a61fd4ca09376e91faae1045bbbe2/lib/helpers/createExtensions.js#L61-L67
interface GulpErrorEvent
{
    uid: number;
    name: string;
    branch: string;
    error: any;
    duration: number[];
    time: number;
}

/**
 * Sets up logging.
 */
function setupLogging(gulp: Gulp): void
{
    gulp.on("start", function(event: GulpStartEvent)
    {
        let info = parseLogCommand(event.name);
        
        if (!info.noLog)
        {
            let logMsg = "";
            
            if (info.isPackage)
            {
                logMsg = `Starting build for package "${chalk.cyan(info.baseName)}"...`;
            }
            else
            {
                logMsg = info.baseName;
            }
            
            console.log(logMsg);
        }
    });
    
    gulp.on("stop", function(event: GulpStopEvent)
    {
        let info = parseLogCommand(event.name);
        
        if (!info.noLog)
        {
            let logMsg = "";
            
            if (info.isPackage)
            {
                logMsg = `Finished build for package "${chalk.cyan(info.baseName)}" in ${chalk.magenta(formatHrTime(event.duration))}`;
            }
            else
            {
                logMsg = info.baseName;
            }
            
            console.log(logMsg);
        }
    });
    
    gulp.on("error", function(event: GulpErrorEvent)
    {
        // This error logging code sucks. Consider cleaning up when errors become prominent.
        let info = parseLogCommand(event.name);
        
        console.log(`${chalk.red("Error in " + event.name + ": ")} ${JSON.stringify(event)}`);
    });
}

setupLogging(gulp);

/**
 * Runs a Gulp task.
 */
export function runGulpTask(task: TaskFunction, cb: () => void = null)
{
    // https://github.com/gulpjs/gulp-cli/blob/master/lib/versioned/%5E4.0.0/index.js#L74
    task(function(err)
    {
        if (err)
        {
            console.error(err);
        }
        
        if (cb)
        {
            cb();
        }
    });
}

interface ILogCommand
{
    baseName: string;
    noLog: boolean;
    isPackage: boolean;
}

/**
 * Parses a log command.
 */
function parseLogCommand(logCommand: string): ILogCommand
{
    let out = {
        baseName: logCommand,
        noLog: false,
        isPackage: false
    };
    
    if (logCommand.startsWith("@RHBUILD::"))
    {
        let command = logCommand.substring("@RHBUILD::".length);
        
        if (command == "NOLOG")
        {
            out.baseName = "";
            out.noLog = true;
        }
        else if (command.startsWith("PACKAGE::"))
        {
            let packageName = command.substring("PACKAGE::".length);
            
            out.baseName = packageName;
            out.isPackage = true;
        }
    }
    
    return out;
}

// Code taken from Gulp.
var units: [unitStr: string, unitBase: number][] = [
    ['h', 3600e9],
    ['min', 60e9],
    ['s', 1e9],
    ['ms', 1e6],
    ['μs', 1e3],
];
  
function formatHrTime(hrtime: number[]): string
{
    if (!Array.isArray(hrtime) || hrtime.length !== 2)
    {
        return '';
    }
    if (typeof hrtime[0] !== 'number' || typeof hrtime[1] !== 'number')
    {
        return '';
    }

    var nano = hrtime[0] * 1e9 + hrtime[1];

    for (var i = 0; i < units.length; i++)
    {
        if (nano < units[i][1])
        {
            continue;
        }

        if (nano >= units[i][1] * 10)
        {
            return Math.round(nano / units[i][1]) + ' ' + units[i][0];
        }

        var s = String(Math.round(nano * 1e2 / units[i][1]));
        if (s.slice(-2) === '00')
        {
            s = s.slice(0, -2);
        } else if (s.slice(-1) === '0')
        {
            s = s.slice(0, -2) + '.' + s.slice(-2, -1);
        } else
        {
            s = s.slice(0, -2) + '.' + s.slice(-2);
        }
        return s + ' ' + units[i][0];
    }

    if (nano > 0)
    {
        return nano + ' ns';
    }

    return '';
}
// end gulp code