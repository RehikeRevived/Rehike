/**
 * @fileoverview Rehike Build System
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import gulp from "gulp";
import * as GulpManager from "./scripts/gulp_manager";
import * as RehikeBuild from "./scripts/rehikebuild_main";
import * as VflGenerator from "./scripts/vfl_gen";
import * as ArgumentsParser from "./scripts/parse_args";
import { Status as BuildTaskStatus } from "./scripts/build_task";

// String.prototype.replaceAll polyfill for Node.js:
if (!String.prototype["replaceAll"])
{
    String.prototype["replaceAll"] = function (str, newStr)
    {
        // If a regex is passed without a global flag, throw the standard TypeError
        if (Object.prototype.toString.call(str) === '[object RegExp]' && !str.global)
        {
            throw new TypeError("replaceAll must be called with a global RegExp");
        }

        // Escape string characters for safe RegExp construction
        const searchPattern = str instanceof RegExp
            ? str
            : new RegExp(str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g');

        return this.replace(searchPattern, newStr);
    };
}

/**
 * Stores a list of all arguments passed to the build system.
 * 
 * @global
 */
let g_args: ArgumentsParser.ArgumentsRecord = ArgumentsParser.getArgs();

/**
 * All common startup tasks for the build environment.
 */
async function CommonStartupTask(): Promise<void>
{
    // If package names to be built are specified in the arguments, then we only
    // build those packages. Otherwise, we'll send an empty array to build all
    // existing packages.
    let packages: string[] = [];
    if ("package" in g_args)
    {
        if (Array.isArray(g_args["package"]))
        {
            packages = g_args["package"];
        }
    }
    
    await Promise.all([
        RehikeBuild.promiseWrapStream(RehikeBuild.Parser.GulpSetupRhBuildTask(packages))
    ]);
}

CommonStartupTask.displayName = "@RHBUILD::NOLOG";

async function BuildAll()
{
    console.error("Getting VFL hashes of all existing static files...");
    await VflGenerator.getHashesOfAllExistingFiles();
    console.error("Done.");

    const iterator = RehikeBuild.BuildTask.getAllBuildTasks();
    
    const tasks: RehikeBuild.BuildTask[] = [];
    
    /*
     * The waiting architecture here is pretty complicated in order to work with
     * Gulp.
     */
    while (iterator.hasNewItems())
    {
        // We continuously get slices in a loop while they're made. This is done
        // in order to dynamically add more Gulp build tasks during the build.
        const slice = iterator.getNext();
        
        tasks.push(...slice.tasks);
        
        await new Promise<void>((resolve, reject) => {
            GulpManager.runGulpTask(
                gulp.parallel(slice.gulpWrappers),
                () => resolve()
            );
        });
    }
    
    // Wait for all RehikeBuild tasks to finish, which may take longer than Gulp:
    await Promise.all(tasks.map(task => task.resolutionPromise));
    
    let completed = 0;
    let failed = 0;
    let upToDate = 0;
    
    for (const task of tasks)
    {
        switch (task.status)
        {
            case BuildTaskStatus.FINISHED:
                completed++;
                break;
                
            case BuildTaskStatus.UP_TO_DATE:
                upToDate++;
                break;
            
            case BuildTaskStatus.ERRORED:
            default:
                failed++;
                break;
        }
    }
    
    await VflGenerator.generateNewCache();
    console.log("All builds complete.");
    
    // Visual-Studio-like build-completion messages:
    console.log(
        `${completed.toLocaleString()} completed, ` +
        `${failed.toLocaleString()} failed, ` +
        `${upToDate.toLocaleString()} up-to-date`
    );
}

BuildAll.displayName = "@RHBUILD::NOLOG";

console.log("Welcome to RehikeBuild!");

switch (true)
{
    default:
    {
        GulpManager.runGulpTask(gulp.series(
            CommonStartupTask,
            BuildAll
        ));
        break;
    }
}