/**
 * @fileoverview Implements the CSS build task.
 * 
 * @author Isabella Lulamoon <kawapure@gmail.com>
 * @author Niko Yamamoto <kirasicecreamm@gmail.com>
 * @author The Rehike Maintainers
 */

import { BuildTask, g_buildTaskRegistry, GulpTask } from "./build_task";

import sassBackend from "sass";
import gulpSassBackend from "gulp-sass";
const GulpSass = gulpSassBackend(sassBackend);
import through2 from "through2";
import path from "path";
import fs from "fs";
import imageSize from "image-size";
import * as RehikeBuild from "./rehikebuild_main";
import * as VflGenerator from "./vfl_gen";

export default class CSSBuildTask extends BuildTask
{
    /**
     * Should we do a 2x resource build too?
     */
    private do2xBuild: boolean = false;
    
    /**
     * A reference to the original descriptor given to the constructor.
     * 
     * This is cloned and modified for preparing the 2x build task.
     */
    private descriptor: RehikeBuild.BuildTaskDescriptor = null;
    
    /**
     * Are we currently doing a 2x resource build?
     * 
     * @type {boolean}
     */
    private is2xBuild: boolean = false;
    
    constructor(
        descriptor: RehikeBuild.BuildTaskDescriptor,
        inputFileNames: string|string[],
        outputFileName: string,
    )
    {
        super(descriptor, inputFileNames, outputFileName);
        
        this.descriptor = descriptor;
        
        if (descriptor.css2xBuild && !descriptor.cssIsCurrently2xBuildTask)
        {
            this.do2xBuild = true;
        }
        
        if (descriptor.cssIsCurrently2xBuildTask)
        {
            this.is2xBuild = true;
        }
    }
    
    protected override _buildGulpTask(): GulpTask
    {
        const task = this._prepareGulpBackend();
        let currentBuildTask = this;
        let result = task
            .pipe(through2.obj(function(file, encoding, callback) {
                file.contents = Buffer.from(currentBuildTask._doRehikeSpriteTransform(file.contents.toString()));
                this.push(file);
                callback();
            }))
            .pipe(GulpSass.sync({ outputStyle: "compressed" }).on("error", GulpSass.logError))
            .pipe(through2.obj(function(file, encoding, callback) {
                if (currentBuildTask.do2xBuild)
                {
                    let descriptor2x = JSON.parse(JSON.stringify(currentBuildTask.descriptor));
                    descriptor2x.cssIsCurrently2xBuildTask = true;
                    
                    let buildTask2x = new CSSBuildTask(
                        descriptor2x, 
                        currentBuildTask.inputFileNames[0],
                        currentBuildTask._determine2xPath(currentBuildTask.outputFileName)
                    );
                    
                    buildTask2x.displayName += "@2x";
                    
                    g_buildTaskRegistry.push(buildTask2x);
                }
                
                this.push(file);
                callback();
            }))
            .pipe(through2.obj(function(file, encoding, callback) {
                file.contents = Buffer.from(currentBuildTask._vflize(file.contents.toString()));
                this.push(file);
                callback();
            }));
        return result;
    }
    
    private _determine2xPath(originalPath: string): string
    {
        let extension = path.extname(originalPath);
        let base = originalPath.split(extension)[0];
        return base + "-2x" + extension;
    }
    
    /**
     * Transforms calls to `rehike.sprite()` to CSS backgrounds.
     */
    _doRehikeSpriteTransform(originalContent: string): string
    {
        try
        {
            // @ts-ignore "Named capturing groups are only available when
            // targeting 'ES2018' or later.ts(1503)" - Erroneously reported.
            let rehikeSpriteCalls = originalContent.matchAll(/\@include\s+rehike\.sprite\s*\((?<arguments>.*?)\)\s*;/g);
            let result = originalContent;
            
            // @ts-ignore "Type 'IterableIterator<RegExpExecArray>' can only be
            // iterated through when using the '--downlevelIteration' flag or
            // with a '--target' of 'es2015' or higher.ts(2802)" - Erroneously
            // reported.
            for (let fnCall of rehikeSpriteCalls)
            {
                let args = fnCall.groups.arguments;
                let parts = args.split(",");
                
                if (parts.length != 5)
                {
                    throw new Error("Argument count mismatch for call to rehike.sprite.");
                }
                
                for (let part of parts)
                {
                    part = part.trim();
                }
                
                for (let i = 0; i < parts.length; i++)
                    parts[i] = parts[i].trim();
                
                // Scale multiplier
                let scale = 1;
                let originalWidthFor2x = 0;
                let originalHeightFor2x = 0;
                
                if (this.is2xBuild)
                {
                    let originalDimensions = imageSize(parts[0].replace("/rehike/static", RehikeBuild.REHIKE_ROOT_DIR + "/static").replace(/"/g, ""));
                    
                    originalWidthFor2x = originalDimensions.width || 0;
                    originalHeightFor2x = originalDimensions.height || 0;
                    
                    parts[0] = this._determine2xPath(parts[0]);
                    scale = 2;
                }
                
                let newText =
                    `background: no-repeat url(${parts[0].replace(/\"/g, "")}) -${parts[1]}px -${parts[2]}px;\n` +
                    (this.is2xBuild
                        ? `background-size: ${originalWidthFor2x}px ${originalHeightFor2x}px;`
                        : ``
                    ) +
                    `width: ${parts[3]}px;\n` +
                    `height: ${parts[4]}px;`;
                    
                result = result.replace(fnCall[0], newText);
            }
            
            return result;
        }
        catch (e)
        {
            console.error(e);
            return "";
        }
    }

    /**
     * Updates all references to versioned files in the CSS file.
     */
    _vflize(fileContents: string): string
    {
        const vflMap = VflGenerator.getCurrentVflMap();

        for (const vflPath in vflMap)
        {
            if (vflPath.startsWith("static/"))
            {
                const prependedPath = RehikeBuild.unwindows(path.join("rehike", vflPath));
                const destinationPath = RehikeBuild.unwindows(path.join("rehike", vflMap[vflPath]));

                fileContents = fileContents.replaceAll(prependedPath, destinationPath);
            }
        }

        return fileContents;
    }
}