/**
 * @fileoverview Main implementations for RehikeBuild
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import * as path from "path";
import assert from "assert/strict";
import Stream from "stream";

import { g_buildTaskRegistry as buildTaskRegistry } from "./build_task";
export { BuildTask } from "./build_task";

// RehikeBuild root directory.
export const REHIKEBUILD_DIR = path.resolve(__dirname, "../");

// Includes should be relative to the src/ directory, and this script resides in
// src/build_tools/scripts, so we need to up two directories.
export const BASE_SRC_DIR = path.resolve(__dirname, "../..");

// Rehike root directory is three directories up from here.
export const REHIKE_ROOT_DIR = path.resolve(__dirname, "../../..");

// Static content directory.
export const REHIKE_STATIC_DIR = path.resolve(REHIKE_ROOT_DIR, "static");

export * as Parser from "./parse_rhbuild";

// Build task backends:
import CSSBuildTask from "./css_build";
import JSBuildTask from "./js_build";

/**
 * Common build configuration options.
 */
export const commonBuildCfg = {
    base: BASE_SRC_DIR,
    root: BASE_SRC_DIR,
    cwd: BASE_SRC_DIR,
};

/**
 * Wraps a Node.js stream for consumption alongside promises.
 */
export function promiseWrapStream(stream: Stream): Promise<void>
{
    return new Promise((resolve, reject) => {
        stream.on("finish", resolve);
        stream.on("end", resolve);
        stream.on("error", reject);
    });
}

export interface BuildTaskDescriptor
{
    baseName: string;
    taskName: string;
    
    jsBuildFiles?: string[];
    jsOutputBundle?: string;

    cssBuildFiles?: Record<string, string>;
    css2xBuild?: boolean;
    cssIsCurrently2xBuildTask?: boolean;

    protobufBuildFiles?: Record<string, string>;
}

const enum SourceLanguageName
{
    CSS = "css",
    JS = "js",
    Protobuf = "protobuf",
}

/**
 * Pushes a list of source files from a .rhbuild file to the global list.
 */
export function pushSourceFiles(descriptor: BuildTaskDescriptor)
{
    assert(typeof descriptor.baseName == "string", JSON.stringify(descriptor));
    
    const basePath = path.dirname(descriptor.baseName);
    
    // Common function to decorate and push entries for all languages, assuming they
    // exist.
    function buildSourceToSource(
        descriptor: BuildTaskDescriptor,
        srcEntry: Record<string, string>,
        languageName: SourceLanguageName,
    )
    {
        assert(typeof srcEntry == "object");

        for (let entryKey in srcEntry)
        {
            // Resolve the full path of the file (relative from the src/ directory).
            // This is necessary because the keys for these maps in .rhbuild files
            // are relative to the .rhbuild file path.
            let fullEntryPath = unwindows(path.resolve(basePath, entryKey));
                
            // The destination path is always relative to the Rehike root directory.
            const normalizedDestPath = unwindows(srcEntry[entryKey]);
            
            let buildTask = null;
            
            switch (languageName)
            {
                case SourceLanguageName.CSS:
                    buildTask = new CSSBuildTask(descriptor, fullEntryPath, normalizedDestPath);
                    break;
            }
            
            if (buildTask)
            {
                buildTaskRegistry.push(buildTask);
            }
        }
    }
    
    function buildManyToOne(
        descriptor: BuildTaskDescriptor,
        srcEntries: string[],
        outputBundle: string,
        languageName: SourceLanguageName,
    )
    {
        assert(typeof srcEntries == "object");
        assert(typeof outputBundle == "string");
        
        // Resolutions of the full paths of the files (relative from the src/ directory).
        const fullEntryPaths = [];
        
        // The destination path is always relative to the Rehike root directory.
        const normalizedDestPath = unwindows(outputBundle);
        
        for (let entry of srcEntries)
        {
            fullEntryPaths.push(
                unwindows(path.resolve(basePath, entry))
            );
        }
        
        let buildTask = null;
        
        switch (languageName)
        {
            case SourceLanguageName.JS:
                buildTask = new JSBuildTask(descriptor, fullEntryPaths, normalizedDestPath);
                break;
        }
        
        if (buildTask)
        {
            buildTaskRegistry.push(buildTask);
        }
    }
    
    if (descriptor.cssBuildFiles != null)
        buildSourceToSource(
            descriptor,
            descriptor.cssBuildFiles, 
            SourceLanguageName.CSS
        );
    
    if (descriptor.jsBuildFiles != null)
        buildManyToOne(
            descriptor,
            descriptor.jsBuildFiles,
            descriptor.jsOutputBundle,
            SourceLanguageName.JS
        );

    if (descriptor.protobufBuildFiles != null)
        buildSourceToSource(
            descriptor,
            descriptor.protobufBuildFiles,
            SourceLanguageName.Protobuf
        );
}

/**
 * Converts a path using Windows separators (\) to Unix ones (/).
 */
export function unwindows(pathToModify: string): string
{
    return pathToModify.replace(new RegExp("\\" + path.sep, "g"), "/");
}