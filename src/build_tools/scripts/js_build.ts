/**
 * @fileoverview Implements the JS build task.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import { BuildTask, GulpTask, Status } from "./build_task";
import * as RehikeBuild from "./rehikebuild_main";

import gulp from "gulp";
import path from "path";
import closureCompilerBackend from "google-closure-compiler";
const GulpClosureCompiler = closureCompilerBackend.gulp();
import GulpPreprocess from "gulp-preprocess";
import { Transform } from "stream";
import Undertaker from "undertaker";

export default class JSBuildTask extends BuildTask
{
    protected override _buildGulpTask(): GulpTask
    {
        const task = this._prepareGulpBackend();
        const self = this;
        let result = task
            .pipe(GulpPreprocess({
                includeBase: path.dirname(this.inputFileNames[0])
            }))
            .pipe(GulpClosureCompiler({
                compilation_level: "SIMPLE_OPTIMIZATIONS",
                //process_closure_primitives: true,
                language_out: "ECMASCRIPT3",
                output_wrapper: "(function(){%output%})();"
            }))
            .on("error", function(err: any)
            {
                self.forwardError(err.message);
                this.emit("end");
            });
        return result;
    }
    
    protected override _prepareGulpBackend(): NodeJS.ReadWriteStream
    {
        const buildFiles = this.inputFileNames.slice(0); // .slice(0) to clone the array
        
        // Commented out because we won't use Closure Compiler's bundling method. It just seems
        // too unstable.
        // // Requirements for Closure Compiler:
        // buildFiles.push(
        //     "build_tools/node_modules/google-closure-library/closure/goog/base.js"
        // );
        
        return gulp.src(buildFiles, RehikeBuild.commonBuildCfg);
    }
}