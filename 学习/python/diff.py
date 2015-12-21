#!/usr/bin/python
#coding=utf-8
'''
通过difflib模块实现文件内容差异对比
'''
import difflib
import sys
import traceback

try:
    file1=sys.argv[1]  #第一个参数接受第一个文件
    file2=sys.argv[2]  #第二个参数接受第二个文件
except Exception,e:  #异常处理,同except Exception as e:
    f=open("/root/py.log",'a') #a表示以追加模式打开 
    print "Error:"+str(e) #e是内部异常识别机制，不会联系到程序代码
    traceback.print_exc(file=f) #追踪到程序的代码
    print "Usage:diff.py file1name file2name"
    sys.exit()

def readfile(filename):
    try:
	fileHandle = open (filename, 'rb') #rb以二进制读模式打开
#	text=fileHandle.read().splitlines() #读取后以行进行分隔
	text=fileHandle.readlines() #读取后以行进行分隔
	fileHandle.close()
	return text
    except IOError as error:
	print('Read file Error:'+str(error))
	sys.exit()

if file1=="" or file2=="":
    print "Usage: diff.py file1name file2name"
    sys.exit()

file1_lines = readfile(file1)
file2_lines = readfile(file2)

d = difflib.HtmlDiff()
print d.make_file(file1_lines,file2_lines)
