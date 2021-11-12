<template>
    <div class="row justify-content-center ">
        <div class="col-md-12 logo-area text-center">
            <img class="logo" src="../assets/img/creativecats.svg" width="310"  alt="logo image">
        </div>
        <div class="col-md-8 mt-5">
            <div class="card">
                <div class="card-header text-center">Converter && Translator</div>
                <div class="card-body">
                    <v-select class="lang-select mt-3" :options="languages" :reduce="language => language.code" label="label" v-model="targetLanguage" :disabled="isConverting" placeholder="Select target language"></v-select>
                    <div class="flex-1 form-ctrl mt-3">
                        <form enctype="multipart/form-data" novalidate>
                            <div class="dropbox">
                                <input type="file" name="file" :disabled="isConverting" @change="filesChange($event.target.files)" 
                                    accept=".pdf, .rtf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt, .text, .gif, .png, .jpg, .jpeg, .jpg, .jfif, .tif, .tiff, .srt" 
                                    class="input-file">
                                <div v-if="tempFileName" class="dropbox-inner">
                                    <div class="img-rt">
                                        <img src="../assets/img/file.png" alt="file image">
                                    </div>
                                    <div class="dropbox-content">
                                        <div class="dropbox-cap1">Uploaded File</div>
                                        <div class="dropbox-cap2">
                                            {{tempFileName}}, or upload file again <span class="f-link">browse</span>
                                        </div>
                                    </div>
                                </div>
                                <div v-else class="dropbox-inner">
                                    <div class="img-rt">
                                        <img src="../assets/img/upload.png" alt="upload image">
                                    </div>
                                    <div class="dropbox-content">
                                        <div class="dropbox-cap1">Upload File</div>
                                        <div class="dropbox-cap2">
                                            drag & drop file here, or <span class="f-link">browse</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="mt-3 text-center">
                        <button type="button" class="btn btn-primary" @click="uploadFile()" :disabled="!targetLanguage || formData == null || isConverting" >Upload</button>
                    </div>
                    <div class="mt-3 text-center">
                        <p>{{ message }}</p>
                        <b-spinner v-show="isConverting" variant="primary" label="Spinning"></b-spinner>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import languages from '../languages.js';
    import { fileUpload, fileConvert, htmlTranslate } from '../utils/fileUtil';
    
    export default {
        mounted() {
            console.log('Component mounted.');
        },
        data: () => {
            return {
                languages: languages,
                tempFileName: '',
                isConverting: false,
                targetLanguage: '',
                formData: null,
                message: '',
                uFileId: -1,
                interval: null,
                isTranslating: false,
                translatedEntityCnt: 0,
                translationFailedCnt: 0,
            }
        },
        methods: {
            uploadFile() {
                this.isConverting = true;
                this.message = 'File uploading...';
                this.formData.append('targetLang', this.targetLanguage);
                fileUpload(this.formData)
                .then(res => {
                    this.uFileId = res.data.uFileId;
                    this.message = 'File uploaded successfully.';
                    this.convertFile();
                })
                .catch(err => {
                    this.isConverting = false;
                    console.log(err);
                    const response = err.response;
                    if (response.status === 500) {
                        this.message = response.data.message;
                    } else {
                        this.message = 'File uploading failed. Please try again.';
                    }
                });
            },
            convertFile() {
                this.message = "File converting...";
                const formData = new FormData();
                formData.append('uFileId', this.uFileId);
                fileConvert(formData)
                .then(res => {
                    this.message = 'File translating...';
                    this.translationFailedCnt = 0;
                    this.interval = setInterval(() => {
                        this.translateHTML();
                    }, 3000);
                })
                .catch(err => {
                    this.isConverting = false;
                    console.log(err);
                    const response = err.response;
                    if (response.status === 500) {
                        this.message = response.data.message || response.message;
                    } else {
                        this.message = 'File converting failed. Please try again.';
                    }
                });
            },
            async translateHTML() {
                if (this.isTranslating) return;
                const formData = new FormData();
                formData.append('uFileId', this.uFileId);
                formData.append('translatedEntityCnt', this.translatedEntityCnt);

                this.isTranslating = true;
                htmlTranslate(formData)
                .then(res => {
                    this.translatedEntityCnt = res.data.translatedEntityCnt;
                    if (res.data.isTranslationFinished) {
                        this.isConverting = false;
                        this.message = 'File translated successfully.';
                        this.translatedEntityCnt = 0;
                        this.translationFailedCnt = 0;
                        clearInterval(this.interval);
                        this.downloadFile(res.data.url, res.data.fileName);
                    }
                    this.isTranslating = false;
                })
                .catch(err => {
                    console.log(err);
                    this.isTranslating = false;
                    this.translationFailedCnt ++;
                    if (this.translationFailedCnt == 10) {
                        this.isConverting = false;
                        this.translatedEntityCnt = 0;
                        this.translationFailedCnt = 0;
                        clearInterval(this.interval);
                        this.message = 'File translation failed. Please try again.';
                    }
                });
            },
            async downloadFile(uri, name) {
                var link = document.createElement("a");
                link.download = name;
                link.href = uri;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },
            filesChange(fileList) {
                // handle file changes
                this.formData = new FormData();
                if (!fileList.length) return;
                // append the files to FormData
                const file = fileList[0];
                this.formData.append('file', file);
                this.formData.append('fileType', file.type);
                this.formData.append('fileName', file.name);
                this.formData.append('fileSize', file.size);
                this.tempFileName = file.name
                console.log("filename", this.tempFileName)
            },
        }
    }
</script>
