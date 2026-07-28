/**
 * @fileoverview Responsible for creating the VFL mapping.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import crypto from "crypto";
import path from "path";
import fs from "fs/promises";
import gulp from "gulp";
import through2 from "through2";

import * as RehikeBuild from "./rehikebuild_main";
import { Status } from "./build_task";

/**
 * The output destination at which to store the VFL cache.
 */
const VFL_OUTPUT_DESTINATION = "includes/static_version_map.json";

/**
 * Stores the VFL cache map.
 * 
 * This maps the original file name to its versioned file name, which allows for
 * easy lookup during runtime.
 * 
 * This is exported to the file specified in {@link VFL_OUTPUT_DESTINATION} in
 * JSON format.
 */
const g_vflMap: Record<string, string> = {};

/**
 * Generates a VFL mapping from a finished build task.
 */
export function generateVflMappingFromBuildTask(buildTask: RehikeBuild.BuildTask): void
{
    if (
        buildTask.status != Status.FINISHING &&
        buildTask.status != Status.FINISHED
    )
    {
        throw new Error("Attempted to generate VFL mapping from unfinished build task");
    }
    
    let fileContents = buildTask._data.contents;
    let origPath = buildTask.outputFileName;
        
    generateVflMappingForFile(origPath, fileContents);
}

/**
 * Generates a VFL mapping from a file's path and contents.
 * 
 * @param {string} filePath 
 * @param {string} fileContents 
 */
function generateVflMappingForFile(filePath: string, fileContents: string): void
{
    // This is actually the same exact hashing algorithm as YouTube's VFL tool itself uses:
    let vflHash = crypto
        .createHash("md5")
        .update(fileContents)
        .digest("base64")
        .substring(0, 6)
        .replace(/\+/g, "-")
        .replace(/\//g, "_");

    let basename = path.basename(filePath, path.extname(filePath));
    let newFileName = basename + "-vfl" + vflHash + path.extname(filePath);
    let newPath = RehikeBuild.unwindows(path.join(path.dirname(filePath), newFileName));
    
    g_vflMap[RehikeBuild.unwindows(filePath)] = newPath;
}

/**
 * Gets the current VFL map.
 */
export function getCurrentVflMap(): Record<string, string>
{
    return g_vflMap;
}

/**
 * Loads the hashes of all existing files into memory.
 */
export async function getHashesOfAllExistingFiles(): Promise<void>
{
    return await RehikeBuild.promiseWrapStream(
        gulp.src("**/*",
            { cwd: RehikeBuild.REHIKE_STATIC_DIR })
        .pipe(through2.obj(function (file, encoding, callback)
        {
            if (file.stat.isDirectory())
            {
                callback();
                return;
            }

            try
            {
                // The destination path is always relative to the Rehike root directory.
                const normalizedDestPath = RehikeBuild.unwindows(
                    path.relative(RehikeBuild.REHIKE_ROOT_DIR, file.path)
                );

                let fileContents = file.contents.toString();
                generateVflMappingForFile(normalizedDestPath, fileContents);
                callback();
            }
            catch (e)
            {
                console.error("Failed to get contents of file: " + file.path);
            }
        })));
}

/**
 * Replaces the VFL cache file with new contents from the build results.
 */
export async function generateNewCache(): Promise<void>
{
    const FILE_PATH = path.join(RehikeBuild.REHIKE_ROOT_DIR, VFL_OUTPUT_DESTINATION);
    
    let vflMapObj = {};
    
    try
    {
        // Using another FS call for readFile here because it seems that
        // truncate() doesn't work right in Node, so you need to hack
        // around it, it seems:
        let fileContents = await fs.readFile(FILE_PATH);
        
        vflMapObj = JSON.parse(fileContents.toString());
    }
    catch (e) {} // ignore invalid JSON
    
    let fh = await fs.open(FILE_PATH, "w");
    
    // Merge updated entries during this RehikeBuild session with entries
    // from the original file:
    for (let key in g_vflMap)
    {
        vflMapObj[key] = g_vflMap[key];
    }
    
    await fh.write(Buffer.from(JSON.stringify(vflMapObj, null, 4)));
    console.log("Wrote VFL cache.");
    
    await fh.sync();
    
    await fh.close();
}