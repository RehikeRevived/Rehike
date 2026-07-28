/**
 * @fileoverview Parsing utilities for rhbuild files.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import gulp from "gulp";
import path from "path";
import through2 from "through2";
import * as RehikeBuild from "./rehikebuild_main";
import assert from "assert/strict";
import { Transform } from "stream";

/**
 * Gulp task for setting up .rhbuild files.
 */
export function GulpSetupRhBuildTask(buildProjects: string[] = []): Transform
{
    let inputSource: string|string[] = "**/.rhbuild";
    
    // If we're given a list of build packages to parse, then we want to only specify
    // those in the build sources. Since the package names correspond to the file-system
    // layout, we just build a static list.
    if (buildProjects.length > 0)
    {
        inputSource = buildProjects.map(item => `${item}/.rhbuild`);
    }
    
    return gulp.src(inputSource, { cwd: RehikeBuild.BASE_SRC_DIR })
        .pipe(gulpParseRhBuild());
}

/**
 * Gulp object wrapper for parsing .rhbuild files.
 */
export function gulpParseRhBuild(): Transform
{
    return through2.obj(function (file, encoding, callback)
    {
        let fileContents = file.contents.toString();
        doParse(file.path, fileContents);
        callback();
    });
}

/**
 * Sets up the parsing environment and parses a .rhbuild file.
 * 
 * @param {string} scriptContents Contents of an .rhbuild file.
 */
export function doParse(filePath: string, scriptContents: string): void
{
    let TASK_NAME = null;
    let JS_BUILD_FILES = null;
    let JS_OUTPUT_BUNDLE = null;
    let CSS_BUILD_FILES = null;
    let CSS_2X_BUILD = false;
    let PROTOBUF_BUILD_FILES = null;
    
    function runInParserContext()
    {
        eval(scriptContents);
    }
    
    runInParserContext();
    
    let gulpTaskName;
    if (TASK_NAME)
    {
        gulpTaskName = TASK_NAME;
    }
    else
    {
        let temp = path.dirname(filePath).split(path.sep);
        
        for (let i = 0, len = temp.length; i < len; i++)
        {
            if (temp[i] == "src")
            {
                temp = temp.slice(i + 1);
                break;
            }
        }
        
        gulpTaskName = "@RHBUILD::PACKAGE::" + temp.join("/");
    }
    
    RehikeBuild.pushSourceFiles({
        baseName: filePath,
        taskName: gulpTaskName,
        jsBuildFiles: JS_BUILD_FILES,
        jsOutputBundle: JS_OUTPUT_BUNDLE,
        cssBuildFiles: CSS_BUILD_FILES,
        css2xBuild: CSS_2X_BUILD,
        protobufBuildFiles: PROTOBUF_BUILD_FILES,
    });
}