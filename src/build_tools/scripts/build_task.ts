/**
 * @fileoverview Provides the base class for Gulp builds tasks.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import gulp from "gulp";
import through2 from "through2";
import path from "path";
import { Transform } from "stream";
import fs from "fs/promises";

import * as RehikeBuild from "./rehikebuild_main";
import * as VflGenerator from "./vfl_gen";
import Undertaker from "undertaker";
import { getArgs } from "./parse_args";

export type GulpTask = Undertaker.Task & Transform;

/**
 * Stores all registered build tasks.
 * 
 * Note that new tasks should always be appended to the end of this array in
 * order for the build system to function correctly. Inserting an item in the
 * middle will mess things up.
 */
export const g_buildTaskRegistry: BuildTask[] = [];

export const enum Status
{
    PENDING,
    FINISHING,
    FINISHED,
    ERRORED,
    UP_TO_DATE,
}

/**
 * Base class for Gulp build tasks.
 * 
 * @abstract
 */
export abstract class BuildTask
{
    protected inputFileNames: string[] = [];
    public outputFileName: string = "";
    public displayName: string = "";
    
    _gulpTask: GulpTask = null; // TODO: Type
    _status: Status = Status.PENDING;

    // This is public since the logging code wants to access it.
    public _deferredErrorMessage: string|null = null;
    
    _data = null; // TODO: Type??
    
    _resolutionPromise = {
        resolve: null,
        promise: null
    };
    
    public get resolutionPromise(): Promise<any>
    {
        return this._resolutionPromise.promise;
    }
    
    constructor(
        descriptor: RehikeBuild.BuildTaskDescriptor,
        inputFileNames: string|string[],
        outputFileName: string,
    )
    {
        if (typeof inputFileNames == "string")
        {
            this.inputFileNames = [inputFileNames];
        }
        else
        {
            this.inputFileNames = inputFileNames;
        }
        
        this.displayName = descriptor.taskName;
        
        this.outputFileName = outputFileName;
        
        if ("verbose" in getArgs())
        {
            console.log(`Created new BuildTask(${JSON.stringify(inputFileNames)}, ${outputFileName})`);
        }
        
        this._resolutionPromise.promise = new Promise((resolve) => {
            this._resolutionPromise.resolve = resolve;
        });
    }
    
    public get gulpTask()
    {
        this._ensureGulpTask();
        
        return this._gulpTask;
    }
    
    public get isPending(): boolean
    {
        return this._status == Status.PENDING;
    }
    
    public get status(): Status
    {
        return this._status;
    }
    
    /**
     * Gets an iterator for all build tasks in the registry.
     */
    public static getAllBuildTasks(): BuildTaskRegistryIterator
    {
        return new BuildTaskRegistryIterator();
    }
    
    /**
     * Ensures that the Gulp task exists, and creates it if it doesn't.
     */
    protected _ensureGulpTask(): void
    {
        if (!this._gulpTask)
        {
            const parent = this;
            if ("verbose" in getArgs())
            {
                console.log("Creating gulp task");
            }
            this._gulpTask = this._buildGulpTask();
            
            this._gulpTask = this._gulpTask.pipe(this._getDataFromStream(this)) as GulpTask;
            
            this._gulpTask.on("finish", async function() {
                if (Status.PENDING != parent._status)
                {
                    if (Status.ERRORED == parent.status)
                    {
                        parent._resolutionPromise.resolve();
                    }
                    return;
                }

                // Perform post-task events:
                parent._status = Status.FINISHING;

                // XXX(niko): Past this point, the function defers execution
                // which will almost certainly be passed to another "finish"
                // event handler for this task. Therefore, they will most likely
                // see the task as FINISHING and not FINISHED.
                await parent._onAllTasksCompleted();
                
                // We're done building, so signal to any outside subscribers:
                parent._status = Status.FINISHED;
                parent._resolutionPromise.resolve(parent._data);
            });
            
            this._gulpTask.on("error", function(e) {
                parent._status = Status.ERRORED;
                parent._resolutionPromise.resolve(e);
                this.emit("end");
            });
        }
    }

    /**
     * Forward an error from a Gulp transform stream.
     */
    protected forwardError(message: string): void
    {
        this._status = Status.ERRORED;
        this._deferredErrorMessage = message;
    }
    
    /**
     * Builds a Gulp task for the file.
     * 
     * @virtual
     */
    protected abstract _buildGulpTask(): GulpTask;
    // {
    //     const gulp = this._prepareGulpBackend();
    //     return gulp;
    // }
    
    /**
     * Sets up the Gulp backend for building the task.
     * 
     * @protected
     */
    protected _prepareGulpBackend(): NodeJS.ReadWriteStream
    {
        return gulp.src(this.inputFileNames, RehikeBuild.commonBuildCfg);
    }
    
    /**
     * Runs when all Gulp tasks are done running.
     * 
     * @virtual
     */
    protected async _onAllTasksCompleted()
    {
        // Ensure that the directories to the file path exist when attempting to load it:
        let fullOutputPath = path.join(RehikeBuild.REHIKE_ROOT_DIR, this.outputFileName);
        
        const dirName = path.dirname(fullOutputPath);
        
        try
        {
            if (!((await fs.stat(dirName)).isDirectory()))
            {
                await fs.mkdir(dirName, { recursive: true });
            }
        }
        catch (e)
        {
            await fs.mkdir(dirName, { recursive: true });
        }
        
        // Write the file:
        const fd = await fs.open(fullOutputPath, "w");
        
        await fd.write(this._data.contents);
        
        if ("verbose" in getArgs())
        {
            console.log(`Wrote out file "${fullOutputPath}"`);
        }
        
        await fd.close();
        
        // Generate VFL mapping:
        VflGenerator.generateVflMappingFromBuildTask(this);
    }
    
    /**
     * Gets the data from the Gulp transform stream.
     */
    _getDataFromStream(targetObj: BuildTask): Transform
    {
        return through2.obj(function(file, encoding, callback) {
            targetObj._data = file;
            
            // This should always be the last step, but just in case, we actually don't
            // push the file in any case.
            callback();
        });
    }
}

/**
 * Iterates the build task registry.
 * 
 * This design exists to allow tasks to be added dynamically during the build process.
 */
class BuildTaskRegistryIterator
{
    /**
     * The latest known item position in the build task registry.
     */
    private _latestKnownItemPosition = 0;
    
    /**
     * Check if new items were added to the registry since the last time we checked.
     */
    public hasNewItems(): boolean
    {
        return this._latestKnownItemPosition < g_buildTaskRegistry.length;
    }
    
    /**
     * Gets the latest unread chunk of build tasks from the registry.
     * 
     * This function is also responsible for the decoration process so that they
     * work with Gulp.
     */
    public getNext(): IIteratorResponse
    {
        const chunk = g_buildTaskRegistry.slice(this._latestKnownItemPosition);
        
        this._latestKnownItemPosition = g_buildTaskRegistry.length;
        
        let gulpWrappers = [];
        let tasks = [];
        
        for (const buildTask of chunk)
        {
            const wrapper = function() {
                return buildTask.gulpTask;
            };
            
            // Inherit the Gulp task name from the wrapped task so that the
            // console logs work correctly:
            wrapper.displayName = buildTask.displayName;
            
            gulpWrappers.push(wrapper);
            tasks.push(buildTask);
        }
        
        return {
            gulpWrappers: gulpWrappers,
            tasks: tasks
        };
    }
}

export interface IIteratorResponse
{
    /**
     * Wrapped tasks for Gulp's Undertaker module.
     * 
     * @type {callback[]}
     */
    gulpWrappers; // TODO: Type.
    
    /**
     * All source build tasks for the chunk.
     * 
     * @type {BuildTask[]}
     */
    tasks: BuildTask[];
}